<?php

namespace App\Filament\Pages;

use App\Services\ModerationQueueService;
use App\Support\Staff;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

class ModerationQueue extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Moderation Queue';

    protected static ?string $title = 'Moderation Queue';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.moderation-queue';

    public ?string $selectedKey = null;

    public string $reason = '';

    public static function canAccess(): bool
    {
        return Staff::isModerator(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = app(ModerationQueueService::class)->count(auth()->user());

        return $count > 0 ? (string) $count : null;
    }

    #[Computed]
    public function items()
    {
        return app(ModerationQueueService::class)->pending(auth()->user());
    }

    #[Computed]
    public function selected(): ?array
    {
        return $this->items->firstWhere('key', $this->selectedKey);
    }

    public function select(string $key): void
    {
        $this->selectedKey = $key;
        $this->reason = '';
    }

    public function approve(): void
    {
        $this->run(fn ($service, $type, $id, $user) => $service->approve($type, $id, $user), 'Tasdiqlandi');
    }

    public function reject(): void
    {
        $reason = $this->reason;

        $this->run(fn ($service, $type, $id, $user) => $service->reject($type, $id, $user, $reason), 'Rad etildi');
    }

    private function run(callable $callback, string $success): void
    {
        $selected = $this->selected;
        if (! $selected) {
            return;
        }

        try {
            $callback(app(ModerationQueueService::class), $selected['type'], $selected['id'], auth()->user());
        } catch (AuthorizationException|DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        } catch (ModelNotFoundException) {
            Notification::make()->title('Element navbatda emas (boshqa moderator hal qilgan bo\'lishi mumkin).')->warning()->send();
            $this->selectedKey = null;
            unset($this->items, $this->selected);

            return;
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();

            return;
        }

        Notification::make()->title($success)->success()->send();
        $this->selectedKey = null;
        $this->reason = '';
        unset($this->items, $this->selected);
    }
}
