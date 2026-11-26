<?php

namespace App\Services;

use App\Enums\ConversationStatus;
use App\Enums\SenderType;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Support\Staff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class SupportInboxService
{
    public function __construct(private SupportChatService $chat) {}

    public function list(User $actor, string $filter): Collection
    {
        Gate::forUser($actor)->authorize('viewAny', SupportConversation::class);

        $closed = ConversationStatus::Closed->value;

        return SupportConversation::query()
            ->with(['user:id,name', 'operator:id,name'])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_type', SenderType::Customer->value)
                ->whereNull('read_at')])
            ->when($filter === 'mine', fn ($q) => $q->where('operator_id', $actor->id)->where('status', '!=', $closed))
            ->when($filter === 'unassigned', fn ($q) => $q->whereNull('operator_id')->where('status', '!=', $closed))
            ->when($filter === 'open', fn ($q) => $q->where('status', '!=', $closed))
            ->when($filter === 'closed', fn ($q) => $q->where('status', $closed))
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();
    }

    public function unreadConversationCount(?User $actor): int
    {
        if (! Staff::isOperator($actor)) {
            return 0;
        }

        return SupportConversation::query()
            ->where('status', '!=', ConversationStatus::Closed->value)
            ->where(fn ($q) => $q->whereNull('operator_id')->orWhere('operator_id', $actor->id))
            ->whereHas('messages', fn ($q) => $q
                ->where('sender_type', SenderType::Customer->value)
                ->whereNull('read_at'))
            ->count();
    }

    public function claim(SupportConversation $conversation, User $actor): SupportConversation
    {
        $conversation->refresh();
        Gate::forUser($actor)->authorize('claim', $conversation);

        $query = SupportConversation::query()->whereKey($conversation->id);
        if (! Staff::isAdmin($actor)) {
            $query->where(fn ($q) => $q->whereNull('operator_id')->orWhere('operator_id', $actor->id));
        }

        $updated = $query->update(['operator_id' => $actor->id, 'status' => ConversationStatus::Assigned->value]);

        if ($updated === 0) {
            throw new AuthorizationException('Suhbatni boshqa operator oldi.');
        }

        return $conversation->refresh();
    }

    public function reply(SupportConversation $conversation, User $actor, string $body): SupportMessage
    {
        $conversation->refresh();

        if ($conversation->operator_id === null && Staff::isOperator($actor)) {
            $conversation = $this->claim($conversation, $actor);
        }

        Gate::forUser($actor)->authorize('reply', $conversation);

        $body = trim($body);
        Validator::make(['body' => $body], [
            'body' => ['required', 'string', 'max:'.config('support.max_length')],
        ])->validate();

        return $this->chat->send($conversation, $body, SenderType::Operator, $actor->id);
    }

    public function close(SupportConversation $conversation, User $actor): void
    {
        $conversation->refresh();
        Gate::forUser($actor)->authorize('close', $conversation);
        $this->chat->close($conversation);
    }

    public function reopen(SupportConversation $conversation, User $actor): void
    {
        $conversation->refresh();
        Gate::forUser($actor)->authorize('reopen', $conversation);

        $conversation->forceFill([
            'status' => $conversation->operator_id ? ConversationStatus::Assigned : ConversationStatus::Open,
            'closed_at' => null,
            'last_message_at' => now(),
        ])->save();
    }

    public function transfer(SupportConversation $conversation, User $actor, User $to): void
    {
        $conversation->refresh();
        Gate::forUser($actor)->authorize('transfer', $conversation);

        if (! Staff::isOperator($to)) {
            throw new AuthorizationException('Faqat operatorga o\'tkazish mumkin.');
        }

        $conversation->forceFill([
            'operator_id' => $to->id,
            'status' => ConversationStatus::Assigned,
        ])->save();

        $this->chat->send($conversation, "Suhbat {$to->name} operatoriga o'tkazildi.", SenderType::System);
    }

    public function markRead(SupportConversation $conversation, User $actor): void
    {
        Gate::forUser($actor)->authorize('view', $conversation);
        $this->chat->markRead($conversation, SenderType::Operator);
    }
}
