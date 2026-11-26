<?php

namespace App\Services;

use App\Enums\ConversationStatus;
use App\Enums\SenderType;
use App\Events\MessageSent;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportChatService
{
    public function visitorToken(Request $request): ?string
    {
        if ($request->user()) {
            return null;
        }

        $token = $request->cookie(config('support.visitor_cookie'));
        if (is_string($token) && $token !== '') {
            return substr($token, 0, 64);
        }

        $token = $request->session()->get('support_visitor');
        if (! is_string($token) || $token === '') {
            $token = (string) Str::uuid();
            $request->session()->put('support_visitor', $token);
        }

        return $token;
    }

    public function findOpen(?User $user, ?string $token): ?SupportConversation
    {
        if (! $user && ! $token) {
            return null;
        }

        return SupportConversation::query()
            ->where('status', '!=', ConversationStatus::Closed->value)
            ->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->whereNull('user_id')->where('visitor_token', $token),
            )
            ->latest('id')
            ->first();
    }

    public function openOrCreate(?User $user, ?string $token, ?string $guestName = null): SupportConversation
    {
        $existing = $this->findOpen($user, $token);
        if ($existing) {
            return $existing;
        }

        return SupportConversation::create([
            'user_id' => $user?->id,
            'visitor_token' => $user ? null : $token,
            'guest_name' => $user ? null : ($guestName ?: null),
            'status' => ConversationStatus::Open,
            'last_message_at' => now(),
        ]);
    }

    public function send(SupportConversation $conversation, string $body, SenderType $type, ?int $senderId = null): SupportMessage
    {
        $message = DB::transaction(function () use ($conversation, $body, $type, $senderId) {
            $message = $conversation->messages()->create([
                'sender_type' => $type,
                'sender_id' => $senderId,
                'body' => $body,
            ]);

            $conversation->forceFill(['last_message_at' => now()])->save();

            return $message;
        });

        event(new MessageSent($message));

        if ($type === SenderType::Customer
            && ! $this->isWithinHours()
            && ! $conversation->messages()->where('sender_type', SenderType::System->value)->exists()) {
            $system = $conversation->messages()->create([
                'sender_type' => SenderType::System,
                'body' => config('support.offline_message'),
            ]);
            event(new MessageSent($system));
        }

        return $message;
    }

    public function latest(SupportConversation $conversation): Collection
    {
        return $conversation->messages()
            ->orderByDesc('id')
            ->limit((int) config('support.history_limit'))
            ->get()
            ->reverse()
            ->values();
    }

    public function after(SupportConversation $conversation, int $afterId): Collection
    {
        return $conversation->messages()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(100)
            ->get();
    }

    public function unreadFor(SupportConversation $conversation, SenderType $reader): int
    {
        return $this->unreadQuery($conversation, $reader)->count();
    }

    public function markRead(SupportConversation $conversation, SenderType $reader): int
    {
        return $this->unreadQuery($conversation, $reader)->update(['read_at' => now()]);
    }

    public function close(SupportConversation $conversation): void
    {
        $conversation->forceFill([
            'status' => ConversationStatus::Closed,
            'closed_at' => now(),
        ])->save();
    }

    public function adoptGuest(User $user, ?string $token = null): void
    {
        $token ??= request()->cookie(config('support.visitor_cookie')) ?: session('support_visitor');
        if (! is_string($token) || $token === '') {
            return;
        }

        $guest = SupportConversation::query()
            ->whereNull('user_id')
            ->where('visitor_token', $token)
            ->where('status', '!=', ConversationStatus::Closed->value)
            ->latest('id')
            ->first();

        if (! $guest) {
            return;
        }

        $mine = $this->findOpen($user, null);

        if (! $mine) {
            $guest->forceFill(['user_id' => $user->id, 'visitor_token' => null])->save();

            return;
        }

        DB::transaction(function () use ($guest, $mine) {
            SupportMessage::where('conversation_id', $guest->id)->update(['conversation_id' => $mine->id]);
            $mine->forceFill(['last_message_at' => now()])->save();
            $guest->delete();
        });
    }

    public function isWithinHours(?CarbonInterface $now = null): bool
    {
        $cfg = config('support.hours');
        if ($cfg['always']) {
            return true;
        }

        $now = ($now ?? now())->copy()->setTimezone($cfg['timezone']);
        $start = $now->copy()->setTimeFromTimeString($cfg['start']);
        $end = $now->copy()->setTimeFromTimeString($cfg['end']);

        return $now->between($start, $end);
    }

    private function unreadQuery(SupportConversation $conversation, SenderType $reader)
    {
        $from = $reader === SenderType::Customer
            ? [SenderType::Operator->value, SenderType::System->value]
            : [SenderType::Customer->value];

        return $conversation->messages()->whereNull('read_at')->whereIn('sender_type', $from);
    }
}
