<?php

namespace App\Filament\Partner\Resources\Products\Schemas;

use App\Models\Product;
use App\Services\PartnerService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug((string) $state))
                        : null),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                // Faqat o'z brendlari. Server tomonda yana tekshiriladi (CreateProduct/EditProduct).
                Select::make('brand_id')
                    ->label('Brand')
                    ->relationship(
                        'brand',
                        'name',
                        modifyQueryUsing: function (Builder $query): Builder {
                            $ids = app(PartnerService::class)->brandIds(auth()->user());

                            return $ids === null ? $query : $query->whereIn('brands.id', $ids);
                        },
                    )
                    ->default(function (): ?int {
                        $ids = app(PartnerService::class)->brandIds(auth()->user());

                        return is_array($ids) && count($ids) === 1 ? $ids[0] : null;
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->preload()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),
                Textarea::make('description')
                    ->rows(4)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Textarea::make('rejection_reason')
                    ->label('Moderator feedback')
                    ->rows(3)
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Product $record): bool => filled($record?->rejection_reason))
                    ->columnSpanFull(),
            ]);
    }
}
