<?php

namespace App\Support;

use App\Models\User;

final class Staff
{
    public static function role(?User $user): string
    {
        $role = $user?->role;

        return $role instanceof \BackedEnum ? (string) $role->value : (string) $role;
    }

    public static function isAdmin(?User $user): bool
    {
        return self::role($user) === 'admin';
    }

    public static function isOperator(?User $user): bool
    {
        return in_array(self::role($user), ['operator', 'admin'], true);
    }

    public static function isModerator(?User $user): bool
    {
        return in_array(self::role($user), ['moderator', 'admin'], true);
    }
}
