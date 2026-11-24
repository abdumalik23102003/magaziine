<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Standart: faqat faol admin ruxsat oladi. Policy o'ziga kerakli metodlarni qayta yozadi
 * (klass metodi trait metodidan ustun turadi).
 */
trait AdminOnlyByDefault
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function delete(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
