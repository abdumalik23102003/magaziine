<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\User;
use App\Support\PasswordlessGuard;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/** Google / GitHub (Laravel Socialite) orqali kelgan shaxsga mos hisobni topadi, bog'laydi yoki yaratadi. */
class SocialAuthService
{
    /** provayder kaliti (config/services.php) => ekrandagi nom */
    public const PROVIDERS = [
        'google' => 'Google',
        'github' => 'GitHub',
    ];

    /**
     * Faqat client_id va client_secret berilgan provayderlar ko'rsatiladi.
     *
     * @return array<string, string>
     */
    public function enabled(): array
    {
        return array_filter(
            self::PROVIDERS,
            fn (string $label, string $provider): bool => filled(config("services.{$provider}.client_id"))
                && filled(config("services.{$provider}.client_secret")),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function isEnabled(string $provider): bool
    {
        return array_key_exists($provider, $this->enabled());
    }

    public function label(string $provider): string
    {
        return self::PROVIDERS[$provider] ?? ucfirst($provider);
    }

    /** @throws ValidationException hisob kirish uchun yaramasa */
    public function resolveUser(string $provider, SocialiteUser $social): User
    {
        $providerUserId = (string) $social->getId();

        if ($providerUserId === '') {
            throw ValidationException::withMessages(['auth' => "{$this->label($provider)} did not return an account id."]);
        }

        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($account !== null) {
            PasswordlessGuard::ensureAllowed($account->user);

            return $account->user;
        }

        $email = $this->verifiedEmail($provider, $social);
        $existing = $email !== null ? User::query()->where('email', $email)->first() : null;

        if ($existing !== null) {
            return $this->linkToExisting($existing, $provider, $providerUserId, $social, $email);
        }

        return $this->register($provider, $providerUserId, $social, $email);
    }

    private function linkToExisting(User $user, string $provider, string $providerUserId, SocialiteUser $social, string $email): User
    {
        PasswordlessGuard::ensureAllowed($user);

        // Faqat tasdiqlangan emailli hisobga bog'lanadi: tasdiqlanmagan hisob boshqa odam tomonidan oldindan ochilgan bo'lishi mumkin.
        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'auth' => 'An account with this email already exists and has not been verified. Please sign in with your password.',
            ]);
        }

        if ($user->socialAccounts()->where('provider', $provider)->exists()) {
            throw ValidationException::withMessages([
                'auth' => "This account is already connected to a different {$this->label($provider)} account.",
            ]);
        }

        $this->createAccount($user, $provider, $providerUserId, $social, $email);

        return $user;
    }

    private function register(string $provider, string $providerUserId, SocialiteUser $social, ?string $email): User
    {
        $name = trim((string) ($social->getName() ?: $social->getNickname()));

        if ($name === '') {
            $name = $email !== null ? Str::before($email, '@') : $this->label($provider).' user';
        }

        try {
            return DB::transaction(function () use ($provider, $providerUserId, $social, $email, $name): User {
                $user = new User([
                    'name' => Str::limit($name, 120, ''),
                    'email' => $email,
                    'password' => Str::random(48),
                ]);

                // Provayder emailni tasdiqlagan bo'lsa (verifiedEmail), u bizda ham tasdiqlangan hisoblanadi.
                $user->forceFill(['email_verified_at' => $email !== null ? now() : null])->save();

                $this->createAccount($user, $provider, $providerUserId, $social, $email);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            // Parallel so'rov xuddi shu hisobni allaqachon yaratgan bo'lishi mumkin.
            $account = SocialAccount::query()
                ->with('user')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($account === null) {
                throw ValidationException::withMessages(['auth' => 'Could not create your account. Please try again.']);
            }

            PasswordlessGuard::ensureAllowed($account->user);

            return $account->user;
        }
    }

    private function createAccount(User $user, string $provider, string $providerUserId, SocialiteUser $social, ?string $email): void
    {
        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'email' => $email ?? (filter_var($social->getEmail(), FILTER_VALIDATE_EMAIL) ? Str::lower((string) $social->getEmail()) : null),
            'name' => $social->getName() ?: $social->getNickname(),
        ]);
    }

    /**
     * Provayder tasdiqlagan email (kichik harflarda) yoki null.
     * Tasdiqlanmagan emailga ishonilmaydi: u orqali boshqa odamning hisobiga kirib bo'lmasligi kerak.
     */
    private function verifiedEmail(string $provider, SocialiteUser $social): ?string
    {
        $email = Str::lower(trim((string) $social->getEmail()));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $raw = property_exists($social, 'user') && is_array($social->user) ? $social->user : [];

        return match ($provider) {
            // Google userinfo javobidagi email_verified bayrog'i.
            'google' => filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN) ? $email : null,
            // GitHub drayveri (user:email scope) faqat asosiy VA tasdiqlangan manzilni qaytaradi.
            'github' => $email,
            default => null,
        };
    }
}
