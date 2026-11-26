<?php

namespace App\Policies;

use App\Models\User;

/** Faqat admin ko'radi. create/update/delete ataylab yo'q: Gate ularni rad etadi. */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }
}
