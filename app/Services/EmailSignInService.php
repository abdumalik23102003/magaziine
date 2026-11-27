<?php

namespace App\Services;

use App\Models\User;
use App\Support\PasswordlessGuard;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Email egaligi kod bilan tasdiqlangach, unga mos hisobni topadi yoki yaratadi. */
class EmailSignInService
{
    /** @throws ValidationException hisob parolsiz kirish uchun yaramasa */
    public function userFor(string $email): User
    {
        $email = LoginCodeService::normalize($email);
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return $this->register($email);
        }

        PasswordlessGuard::ensureAllowed($user);

        // Parol bilan ro'yxatdan o'tgan va emaili tasdiqlanmagan hisobga kod bilan kirilmaydi:
        // kimdir boshqaning emailini oldindan band qilib qo'ygan bo'lishi mumkin.
        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'auth' => 'This email is registered with a password and has not been verified yet. Please sign in with your password.',
            ]);
        }

        return $user;
    }

    private function register(string $email): User
    {
        $name = Str::squish(Str::title(str_replace(['.', '_', '-', '+'], ' ', Str::before($email, '@'))));

        $user = new User([
            'name' => Str::limit($name !== '' ? $name : 'Customer', 120, ''),
            'email' => $email,
            'password' => Str::random(48),
        ]);

        try {
            // Kod emailga yetib kelganini isbotlaydi, shuning uchun email darhol tasdiqlangan hisoblanadi.
            $user->forceFill(['email_verified_at' => now()])->save();
        } catch (UniqueConstraintViolationException) {
            return User::query()->where('email', $email)->firstOrFail();
        }

        return $user;
    }
}
