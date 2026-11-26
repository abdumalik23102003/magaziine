<?php

namespace App\Policies;

use App\Enums\ConversationStatus;
use App\Models\SupportConversation;
use App\Models\User;
use App\Support\Staff;

class SupportConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return Staff::isOperator($user);
    }

    public function view(User $user, SupportConversation $conversation): bool
    {
        return Staff::isOperator($user);
    }

    public function claim(User $user, SupportConversation $conversation): bool
    {
        return Staff::isOperator($user)
            && $conversation->status !== ConversationStatus::Closed
            && ($conversation->operator_id === null || $conversation->operator_id === $user->id || Staff::isAdmin($user));
    }

    public function reply(User $user, SupportConversation $conversation): bool
    {
        return Staff::isOperator($user)
            && $conversation->status !== ConversationStatus::Closed
            && ($conversation->operator_id === $user->id || Staff::isAdmin($user));
    }

    public function close(User $user, SupportConversation $conversation): bool
    {
        return $this->reply($user, $conversation);
    }

    public function reopen(User $user, SupportConversation $conversation): bool
    {
        return Staff::isOperator($user)
            && $conversation->status === ConversationStatus::Closed
            && ($conversation->operator_id === null || $conversation->operator_id === $user->id || Staff::isAdmin($user));
    }

    public function transfer(User $user, SupportConversation $conversation): bool
    {
        return Staff::isAdmin($user);
    }
}
