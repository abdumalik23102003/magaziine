<?php

namespace App\Services;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Support\OneTimeCode;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Emailga bir martalik kod yuboradi va tekshiradi. Kod bazada faqat hash ko'rinishida turadi. */
class LoginCodeService
{
    public const TTL_MINUTES = 10;

    /** Bitta kod uchun noto'g'ri urinishlar limiti. */
    public const MAX_ATTEMPTS = 5;

    /** Bir emailga ketma-ket kod yuborish orasidagi pauza. */
    public const RESEND_SECONDS = 60;

    /** Bir emailga soatiga ko'pi bilan nechta kod. */
    public const MAX_PER_HOUR = 5;

    public static function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    /** @throws ValidationException pauza yoki soatlik limit buzilsa, yoki xat jo'natib bo'lmasa */
    public function issue(string $email, ?string $ip = null): void
    {
        $email = self::normalize($email);
        $code = OneTimeCode::generate();

        try {
            // Tekshiruv va yaratish bitta qulf ostida: parallel so'rovlar pauza/soatlik limitni aylanib o'tolmasin.
            $row = Cache::lock('login-code:'.sha1($email), 10)->block(5, fn (): LoginCode => $this->createRow($email, $code, $ip));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['email' => 'Please wait a moment and try again.']);
        }

        try {
            Mail::to($email)->send(new LoginCodeMail($code, self::TTL_MINUTES));
        } catch (Throwable $e) {
            report($e);
            $row->delete();

            throw ValidationException::withMessages(['email' => 'We could not send the email right now. Please try again in a minute.']);
        }
    }

    private function createRow(string $email, string $code, ?string $ip): LoginCode
    {
        $latest = LoginCode::query()->where('email', $email)->latest('id')->first();

        if ($latest !== null) {
            $wait = $latest->created_at->getTimestamp() + self::RESEND_SECONDS - now()->getTimestamp();

            if ($wait > 0) {
                throw ValidationException::withMessages(['email' => "Please wait {$wait} seconds before requesting another code."]);
            }
        }

        $lastHour = LoginCode::query()->where('email', $email)->where('created_at', '>', now()->subHour())->count();

        if ($lastHour >= self::MAX_PER_HOUR) {
            throw ValidationException::withMessages(['email' => 'Too many codes requested for this email. Please try again later.']);
        }

        return DB::transaction(function () use ($email, $code, $ip): LoginCode {
            // Yangi kod eskisini bekor qiladi: bir vaqtda faqat bitta kod ishlaydi.
            LoginCode::query()->where('email', $email)->whereNull('used_at')->update(['used_at' => now()]);

            return LoginCode::query()->create([
                'email' => $email,
                'code_hash' => OneTimeCode::hash($email, $code, (string) config('app.key')),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'ip_address' => $ip,
            ]);
        });
    }

    /** To'g'ri bo'lsa kodni sarflaydi (qayta ishlamaydi) va true qaytaradi. */
    public function verify(string $email, string $code): bool
    {
        $email = self::normalize($email);
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== OneTimeCode::LENGTH) {
            return false;
        }

        $row = LoginCode::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($row === null) {
            return false;
        }

        // Urinish atomik hisoblanadi: parallel so'rovlar limitni aylanib o'tolmasin.
        $counted = LoginCode::query()
            ->whereKey($row->id)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->increment('attempts');

        if ($counted === 0) {
            return false;
        }

        if (! hash_equals($row->code_hash, OneTimeCode::hash($email, $code, (string) config('app.key')))) {
            return false;
        }

        // Kod faqat bir marta ishlaydi (parallel ikki so'rovdan faqat bittasi yutadi).
        return LoginCode::query()->whereKey($row->id)->whereNull('used_at')->update(['used_at' => now()]) === 1;
    }
}
