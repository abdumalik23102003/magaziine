<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyByDefault;

/**
 * admin: hammasi (trait before). moderator: qoralamadan boshqasini ko'radi/tahrirlaydi, approve/reject.
 * partner: faqat o'z brendi maqolalari; faqat draft/rejected ni o'zgartiradi va review ga yuboradi.
 * publish (to'g'ridan-to'g'ri e'lon qilish): faqat admin, shuning uchun bu yerda false.
 */
class PostPolicy
{
    use AdminOnlyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Moderator, UserRole::Partner);
    }

    public function view(User $user, Post $post): bool
    {
        return $this->moderates($user, $post) || $this->ownsPost($user, $post);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Partner);
    }

    public function update(User $user, Post $post): bool
    {
        return $this->moderates($user, $post) || $this->ownsEditablePost($user, $post);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->ownsEditablePost($user, $post);
    }

    public function submitForReview(User $user, Post $post): bool
    {
        return $this->ownsEditablePost($user, $post);
    }

    public function approve(User $user, Post $post): bool
    {
        return $user->hasRole(UserRole::Moderator);
    }

    public function reject(User $user, Post $post): bool
    {
        return $user->hasRole(UserRole::Moderator);
    }

    public function publish(User $user, Post $post): bool
    {
        return false;
    }

    /** Moderator hamkorning qoralamasini ko'rmaydi: u faqat review ga yuborilganini ko'radi. */
    private function moderates(User $user, Post $post): bool
    {
        return $user->hasRole(UserRole::Moderator) && $post->status !== PostStatus::Draft;
    }

    private function ownsPost(User $user, Post $post): bool
    {
        return $user->hasRole(UserRole::Partner) && $user->ownsBrand($post->brand_id);
    }

    private function ownsEditablePost(User $user, Post $post): bool
    {
        return $this->ownsPost($user, $post) && $post->isEditableByPartner();
    }
}
