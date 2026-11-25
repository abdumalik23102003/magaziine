<?php

namespace App\Filament\Resources\Products\Actions;

use App\Enums\ApprovalStatus;
use App\Models\Product;
use App\Services\ProductService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/** Mahsulot moderatsiyasi tugmalari (admin jadvali va hamkor sahifasida bir xil). Ko'rinish ham, bajarish ham policy orqali. */
class ProductActions
{
    public static function submit(): Action
    {
        return Action::make('submit')
            ->label('Resubmit for review')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->requiresConfirmation()
            ->visible(fn (Product $record): bool => in_array($record->approval_status, [ApprovalStatus::Draft, ApprovalStatus::Rejected], true)
                && (bool) auth()->user()?->can('submitForReview', $record))
            ->action(fn (Product $record) => self::run(fn () => app(ProductService::class)->submit(self::authorized($record, 'submitForReview')), 'Submitted for review'));
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Product $record): bool => $record->approval_status === ApprovalStatus::Pending && (bool) auth()->user()?->can('approve', $record))
            ->action(fn (Product $record) => self::run(fn () => app(ProductService::class)->approve(self::authorized($record, 'approve'), auth()->user()), 'Product approved'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->schema([
                Textarea::make('reason')
                    ->label('Reason (the partner will see this)')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4),
            ])
            ->visible(fn (Product $record): bool => $record->approval_status === ApprovalStatus::Pending && (bool) auth()->user()?->can('reject', $record))
            ->action(fn (Product $record, array $data) => self::run(fn () => app(ProductService::class)->reject(self::authorized($record, 'reject'), auth()->user(), (string) $data['reason']), 'Product rejected'));
    }

    private static function authorized(Product $record, string $ability): Product
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
