<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyByDefault;

class BrandPolicy
{
    use AdminOnlyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Moderator, UserRole::Partner);
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->hasRole(UserRole::Moderator)
            || ($user->hasRole(UserRole::Partner) && $user->ownsBrand($brand->id));
    }

    /** Yangi brendni faqat admin ochadi; hamkor faqat o'z brendi profilini tahrirlaydi. */
    public function update(User $user, Brand $brand): bool
    {
        return $user->hasRole(UserRole::Partner) && $user->ownsBrand($brand->id);
    }
}
