<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyByDefault;

class ProductPolicy
{
    use AdminOnlyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Moderator, UserRole::Partner);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Moderator) || $this->ownsProduct($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Partner);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /** Moderatsiya (5-faza dan keyin ishlatiladi). */
    public function approve(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Moderator);
    }

    public function reject(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Moderator);
    }

    /** Hamkor rad etilgan yoki qoralama mahsulotni qayta ko'rib chiqishga yuboradi. */
    public function submitForReview(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    private function ownsProduct(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Partner) && $user->ownsBrand($product->brand_id);
    }
}
