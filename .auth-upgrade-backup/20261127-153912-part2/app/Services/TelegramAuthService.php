<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\TelegramLoginWidget;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Telegram Mini App: initData imzosini tekshiradi va Telegram foydalanuvchisiga mos hisobni beradi. */
class TelegramAuthService
{
    /** auth_date serverdan shuncha soniya "kelajakda" bo'lsa ham qabul qilinadi (soat farqi). */
    private const CLOCK_SKEW = 300;

    /**
     * https://core.telegram.org/bots/webapps (Validating data received via the Mini App)
     *
     * @return array<string, mixed> Telegram "user" obyekti
     */
    public function verify(string $initData): array
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            Log::warning('Telegram sign-in: TELEGRAM_BOT_TOKEN is not set.');
            $this->fail('Telegram sign-in is not configured yet.');
        }

        $fields = $this->parse($initData);
        $hash = $fields['hash'] ?? '';
        unset($fields['hash']);
        ksort($fields, SORT_STRING);

        $check = collect($fields)->map(fn ($value, $key): string => "{$key}={$value}")->implode("\n");
        $secret = hash_hmac('sha256', $token, 'WebAppData', true);

        if ($hash === '' || ! hash_equals(hash_hmac('sha256', $check, $secret), $hash)) {
            $this->fail('Telegram data could not be verified.');
        }

        $age = now()->getTimestamp() - (int) ($fields['auth_date'] ?? 0);

        if ($age > (int) config('telegram.init_data_max_age') || $age < -self::CLOCK_SKEW) {
            $this->fail('Telegram session expired. Close the shop and open it again.');
        }

        $user = json_decode($fields['user'] ?? '', true);

        if (! is_array($user) || ! is_int($user['id'] ?? null) || $user['id'] <= 0) {
            $this->fail('Telegram data could not be verified.');
        }

        return $user;
    }

    /**
     * Saytdagi Telegram Login Widget (data-auth-url bo'yicha kelgan so'rov maydonlari) imzosini tekshiradi.
     * https://core.telegram.org/widgets/login-legacy
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed> userFor() ga mos Telegram "user" massivi
     */
    public function verifyWidget(array $query): array
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            Log::warning('Telegram sign-in: TELEGRAM_BOT_TOKEN is not set.');
            $this->fail('Telegram sign-in is not configured yet.');
        }

        if (! TelegramLoginWidget::isValid($query, $token)) {
            $this->fail('Telegram data could not be verified.');
        }

        $age = now()->getTimestamp() - (int) ($query['auth_date'] ?? 0);

        if ($age > (int) config('telegram.widget_max_age') || $age < -self::CLOCK_SKEW) {
            $this->fail('Telegram session expired. Please try again.');
        }

        $id = $query['id'] ?? '';

        if (! is_string($id) || ! ctype_digit($id) || (int) $id <= 0) {
            $this->fail('Telegram data could not be verified.');
        }

        return [
            'id' => (int) $id,
            'first_name' => (string) ($query['first_name'] ?? ''),
            'last_name' => (string) ($query['last_name'] ?? ''),
            'username' => isset($query['username']) ? (string) $query['username'] : null,
        ];
    }

    /** @param  array<string, mixed>  $telegram  verify() qaytargan massiv */
    public function userFor(array $telegram): User
    {
        $user = User::query()->where('telegram_id', $telegram['id'])->first() ?? $this->register($telegram);

        if (! $user->is_active) {
            $this->fail('This account is disabled.');
        }

        // Parolsiz kirish faqat mijoz hisobiga: xodim va hamkor panellari Telegram orqali ochilmasin.
        if (! $user->hasRole(UserRole::Customer)) {
            $this->fail('Staff accounts must sign in on the website.');
        }

        $username = is_string($telegram['username'] ?? null) ? Str::limit($telegram['username'], 64, '') : null;
        $user->forceFill(['telegram_username' => $username])->save();

        return $user;
    }

    /** @param  array<string, mixed>  $telegram */
    private function register(array $telegram): User
    {
        $name = Str::squish(($telegram['first_name'] ?? '').' '.($telegram['last_name'] ?? ''));

        // Telegram email bermaydi: email bo'sh (NULL) qoladi. Haqiqiy emailni foydalanuvchi keyin o'zi qo'shadi.
        $user = new User([
            'name' => Str::limit($name !== '' ? $name : 'Telegram user', 120, ''),
            'password' => Str::random(48),
        ]);

        try {
            $user->forceFill(['telegram_id' => $telegram['id']])->save();
        } catch (UniqueConstraintViolationException) {
            return User::query()->where('telegram_id', $telegram['id'])->firstOrFail();
        }

        return $user;
    }

    /** @return array<string, string> */
    private function parse(string $initData): array
    {
        $fields = [];

        foreach (explode('&', $initData) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');

            if ($key !== '') {
                $fields[urldecode($key)] = urldecode($value);
            }
        }

        return $fields;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['init_data' => $message]);
    }
}
