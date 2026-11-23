<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('icon')
                    ->maxLength(255)
                    ->placeholder('icon-bread-baguette.png')
                    ->helperText('public/foodmart/images ichidagi fayl nomi (bo\'sh qoldirsa standart ikonka chiqadi).'),
                Select::make('parent_id')
                    ->label('Parent category')
                    ->relationship(
                        'parent',
                        'name',
                        modifyQueryUsing: fn (Builder $query, ?Model $record) => $record
                            ? $query->whereNotIn('id', [$record->getKey(), ...$record->descendantIds()])
                            : $query,
                    )
                    ->searchable()
                    ->preload(),
            ]);
    }
}
