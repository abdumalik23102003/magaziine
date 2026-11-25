<?php

namespace App\Filament\Resources\Posts\Actions;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\PostService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/** Moderatsiya tugmalari: jadvalda ham, tahrirlash sahifasida ham bir xil. Ko'rinish ham, bajarish ham policy orqali. */
class PostActions
{
    public static function submit(): Action
    {
        return Action::make('submit')
            ->label('Submit for review')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->requiresConfirmation()
            ->visible(fn (Post $record): bool => $record->isEditableByPartner() && (bool) auth()->user()?->can('submitForReview', $record))
            ->action(fn (Post $record) => self::run(fn () => app(PostService::class)->submit(self::authorized($record, 'submitForReview')), 'Submitted for review'));
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Post $record): bool => $record->status === PostStatus::Pending && (bool) auth()->user()?->can('approve', $record))
            ->action(fn (Post $record) => self::run(fn () => app(PostService::class)->approve(self::authorized($record, 'approve'), auth()->user()), 'Post published'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->schema([
                Textarea::make('reason')
                    ->label('Reason (the author will see this)')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4),
            ])
            ->visible(fn (Post $record): bool => $record->status === PostStatus::Pending && (bool) auth()->user()?->can('reject', $record))
            ->action(fn (Post $record, array $data) => self::run(fn () => app(PostService::class)->reject(self::authorized($record, 'reject'), auth()->user(), (string) $data['reason']), 'Post rejected'));
    }

    /** Faqat admin: moderatsiyasiz e'lon qilish. */
    public static function publish(): Action
    {
        return Action::make('publish')
            ->label('Publish now')
            ->icon('heroicon-o-rocket-launch')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Post $record): bool => $record->status !== PostStatus::Published && (bool) auth()->user()?->can('publish', $record))
            ->action(fn (Post $record) => self::run(fn () => app(PostService::class)->publish(self::authorized($record, 'publish'), auth()->user()), 'Post published'));
    }

    private static function authorized(Post $record, string $ability): Post
    {
        abort_unless(auth()->user()?->can($ability, $record), 403);

        return $record;
    }

    private static function run(callable $transition, string $success): void
    {
        try {
            $transition();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($success)->success()->send();
    }
}
