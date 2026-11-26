<?php

namespace App\Filament\Pages;

use App\Models\CannedReply;
use App\Models\SupportConversation;
use App\Models\User;
use App\Services\SupportInboxService;
use App\Support\Staff;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class SupportInbox extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Support Inbox';

    protected static ?string $title = 'Support Inbox';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.support-inbox';

    #[Url]
    public string $filter = 'mine';

    public ?int $selectedId = null;

    public string $reply = '';

    public ?int $transferTo = null;

    public static function canAccess(): bool
    {
        return Staff::isOperator(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = app(SupportInboxService::class)->unreadConversationCount(auth()->user());

        return $count > 0 ? (string) $count : null;
    }

    #[Computed]
    public function conversations()
    {
        return app(SupportInboxService::class)->list(auth()->user(), $this->filter);
    }

    #[Computed]
    public function selected(): ?SupportConversation
    {
        return $this->selectedId
            ? SupportConversation::with(['user:id,name', 'operator:id,name'])->find($this->selectedId)
            : null;
    }

    #[Computed]
    public function thread()
    {
        return $this->selected?->messages()->orderBy('id')->limit(200)->get() ?? collect();
    }

    #[Computed]
    public function canned()
    {
        return CannedReply::orderBy('title')->get(['id', 'title']);
    }

    #[Computed]
    public function operators()
    {
        return User::query()->whereIn('role', ['operator', 'admin'])->orderBy('name')->get(['id', 'name']);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['mine', 'unassigned', 'open', 'closed'], true) ? $filter : 'mine';
    }

    public function select(int $id): void
    {
        $conversation = SupportConversation::findOrFail($id);
        $this->guard(function () use ($conversation) {
            app(SupportInboxService::class)->markRead($conversation, auth()->user());
            $this->selectedId = $conversation->id;
            $this->reply = '';
        });
    }

    public function assignToMe(): void
    {
        $this->act(fn ($c, $s, $u) => $s->claim($c, $u));
    }

    public function sendReply(): void
    {
        $text = $this->reply;

        $this->act(function ($c, $s, $u) use ($text) {
            $s->reply($c, $u, $text);
            $this->reply = '';
        });
    }

    public function closeConversation(): void
    {
        $this->act(fn ($c, $s, $u) => $s->close($c, $u));
    }

    public function reopenConversation(): void
    {
        $this->act(fn ($c, $s, $u) => $s->reopen($c, $u));
    }

    public function transferConversation(): void
    {
        $target = $this->transferTo ? User::find($this->transferTo) : null;
        if (! $target) {
            return;
        }

        $this->act(fn ($c, $s, $u) => $s->transfer($c, $u, $target));
    }

    public function insertCanned(int $id): void
    {
        $canned = CannedReply::find($id);
        if ($canned) {
            $this->reply = trim($this->reply.' '.$canned->body);
        }
    }

    private function act(callable $callback): void
    {
        $conversation = $this->selectedId ? SupportConversation::find($this->selectedId) : null;
        if (! $conversation) {
            return;
        }

        $this->guard(fn () => $callback($conversation, app(SupportInboxService::class), auth()->user()));
        unset($this->conversations, $this->selected, $this->thread);
    }

    private function guard(callable $callback): void
    {
        try {
            $callback();
        } catch (AuthorizationException $e) {
            Notification::make()->danger()->title($e->getMessage() ?: 'Bu amalga ruxsat yo\'q yoki suhbat boshqa operatorga biriktirilgan.')->send();
        } catch (ValidationException $e) {
            Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();
        }
    }
}
