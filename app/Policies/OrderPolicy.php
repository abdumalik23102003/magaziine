<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyByDefault;

/** Buyurtmalar: admin va operator (ko'rish + status o'zgartirish). */
class OrderPolicy
{
    use AdminOnlyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Operator);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasRole(UserRole::Operator);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasRole(UserRole::Operator);
    }
}
