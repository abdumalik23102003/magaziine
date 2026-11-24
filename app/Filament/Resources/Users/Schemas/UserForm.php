<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSelf = fn (?User $record): bool => $record !== null && $record->is(auth()->user());

        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('phone')->tel()->maxLength(32),
                TextInput::make('address')->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Tahrirlashda bo\'sh qoldirilsa parol o\'zgarmaydi.'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default(UserRole::Customer->value)
                    ->required()
                    ->live()
                    ->disabled($isSelf)
                    ->helperText('O\'z rolingizni o\'zgartira olmaysiz.'),
                Select::make('brands')
                    ->relationship('brands', 'name')
                    ->multiple()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Partner, UserRole::Partner->value], true))
                    ->helperText('Hamkor faqat shu brendlarni boshqaradi.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->disabled($isSelf)
                    ->helperText('O\'chirilsa, foydalanuvchi tizimga kira olmaydi.'),
            ]);
    }
}
