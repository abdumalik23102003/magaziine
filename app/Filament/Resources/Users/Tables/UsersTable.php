<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->placeholder('No email'),
                TextColumn::make('sign_in_methods')
                    ->label('Sign-in')
                    ->badge()
                    ->state(fn (User $record): array => self::signInMethods($record)),
                TextColumn::make('phone')->placeholder('-'),
                TextColumn::make('role')->badge()->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('orders_count')->counts('orders')->label('Orders')->sortable(),
                TextColumn::make('created_at')->label('Joined')->dateTime()->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('socialAccounts'))
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('role')->options(UserRole::class),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /** @return list<string> foydalanuvchi qaysi usullar bilan kira oladi */
    private static function signInMethods(User $user): array
    {
        $methods = [];

        if ($user->email !== null) {
            $methods[] = 'Password / email code';
        }

        if ($user->telegram_id !== null) {
            $methods[] = 'Telegram';
        }

        foreach ($user->socialAccounts as $account) {
            $methods[] = ucfirst($account->provider);
        }

        return $methods;
    }
}
