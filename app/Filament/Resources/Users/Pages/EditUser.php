<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $guarded = Arr::only($data, ['role', 'is_active']);

        // O'zini pasaytirish yoki o'chirib qo'yishdan himoya (forma tomonda ham disabled).
        if ($record->is(auth()->user())) {
            $guarded = [];
        }

        $record->fill(Arr::except($data, ['role', 'is_active']));
        $record->forceFill($guarded);
        $record->save();

        return $record;
    }
}
