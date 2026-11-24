<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /** "Cancelled" tanlansa, oddiy update emas: ombor qoldig'i ham qaytariladi. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $becomesCancelled = ($data['status'] ?? null) === OrderStatus::Cancelled->value
            && $record->status !== OrderStatus::Cancelled;

        if ($becomesCancelled) {
            $record->cancel();
            unset($data['status']);
        }

        if ($data !== []) {
            $record->update($data);
        }

        return $record;
    }
}
