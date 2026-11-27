<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Parolsiz kirish (email kod, Google, GitHub) faqat faol mijoz hisobiga ruxsat beradi.
 * Xodim va hamkor panellari faqat parol bilan ochiladi (Telegram'dagi qoida bilan bir xil).
 */
final class PasswordlessGuard
{
    public static function ensureAllowed(User $user): void
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages(['auth' => 'This account is disabled.']);
        }

        if (! $user->hasRole(UserRole::Customer)) {
            throw ValidationException::withMessages(['auth' => 'Staff and partner accounts must sign in with email and password.']);
        }
    }
}
