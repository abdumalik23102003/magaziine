<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** role va is_active mass-assign emas: aniq forceFill (faqat admin bu sahifaga kira oladi). */
    protected function handleRecordCreation(array $data): Model
    {
        $guarded = Arr::only($data, ['role', 'is_active']);

        $user = new User(Arr::except($data, ['role', 'is_active']));
        $user->forceFill([
            'role' => $guarded['role'] ?? UserRole::Customer->value,
            'is_active' => $guarded['is_active'] ?? true,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
