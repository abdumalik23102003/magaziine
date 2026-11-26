<?php

namespace App\Http\Controllers;

use App\Enums\SenderType;
use App\Http\Requests\PollSupportMessagesRequest;
use App\Http\Requests\SendSupportMessageRequest;
use App\Models\SupportConversation;
use App\Services\SupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SupportChatController extends Controller
{
    public function __construct(private SupportChatService $chat) {}

    public function show(PollSupportMessagesRequest $request): JsonResponse
    {
        $conversation = $this->chat->findOpen($request->user(), $this->chat->visitorToken($request));

        if (! $conversation) {
            return response()->json($this->payload(null, collect()));
        }

        if ($request->boolean('open')) {
            $this->chat->markRead($conversation, SenderType::Customer);
        }

        return response()->json($this->payload($conversation, $this->chat->latest($conversation)));
    }

    public function messages(PollSupportMessagesRequest $request): JsonResponse
    {
        $conversation = $this->chat->findOpen($request->user(), $this->chat->visitorToken($request));

        if (! $conversation) {
            return response()->json($this->payload(null, collect()));
        }

        $messages = $this->chat->after($conversation, $request->integer('after'));

        if ($request->boolean('open')) {
            $this->chat->markRead($conversation, SenderType::Customer);
        }

        return response()->json($this->payload($conversation, $messages));
    }

    public function store(SendSupportMessageRequest $request): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->chat->openOrCreate(
            $user,
            $this->chat->visitorToken($request),
            $request->validated('guest_name'),
        );

        $before = (int) $conversation->messages()->max('id');
        $this->chat->send($conversation, $request->validated('body'), SenderType::Customer, $user?->id);

        return response()->json($this->payload($conversation->refresh(), $this->chat->after($conversation, $before)), 201);
    }

    public function close(Request $request): JsonResponse
    {
        $conversation = $this->chat->findOpen($request->user(), $this->chat->visitorToken($request));

        if ($conversation) {
            $this->chat->close($conversation);
        }

        return response()->json($this->payload(null, collect()));
    }

    private function payload(?SupportConversation $conversation, Collection $messages): array
    {
        return [
            'conversation' => $conversation ? [
                'status' => $conversation->status->value,
                'guest_name' => $conversation->guest_name,
            ] : null,
            'messages' => $messages->map(fn ($m) => $m->toPayload())->values(),
            'unread' => $conversation ? $this->chat->unreadFor($conversation, SenderType::Customer) : 0,
            'last_id' => (int) ($messages->last()?->id ?? 0),
            'working' => $this->chat->isWithinHours(),
        ];
    }
}
