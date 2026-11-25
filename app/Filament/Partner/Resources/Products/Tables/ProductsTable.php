<?php

namespace App\Filament\Partner\Resources\Products\Tables;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\Products\Actions\ProductActions;
use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('mainImage.url')
                    ->label('Image')
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Brand'),
                TextColumn::make('approval_status')
                    ->label('Moderation')
                    ->badge()
                    ->tooltip(fn (Product $record): ?string => $record->rejection_reason),
                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('Moderation')
                    ->options(ApprovalStatus::class),
            ])
            ->recordActions([
                ProductActions::submit(),
                EditAction::make(),
            ]);
    }
}
