<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Actions\PostActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_url')
                    ->label('Cover')
                    ->square(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->placeholder('FoodMart')
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options(PostStatus::class),
                SelectFilter::make('brand')->relationship('brand', 'name'),
            ])
            ->recordActions([
                PostActions::approve(),
                PostActions::reject(),
                PostActions::submit(),
                PostActions::publish(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
