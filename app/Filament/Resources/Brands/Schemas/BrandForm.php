<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        $imageTypes = ['image/png', 'image/jpeg', 'image/webp'];

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
                TextInput::make('tagline')
                    ->maxLength(160)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(5)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://example.com'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->acceptedFileTypes($imageTypes)
                    ->disk('public')
                    ->directory('brands/logos')
                    ->visibility('public')
                    ->maxSize(2048),
                FileUpload::make('cover_path')
                    ->label('Cover image')
                    ->image()
                    ->acceptedFileTypes($imageTypes)
                    ->disk('public')
                    ->directory('brands/covers')
                    ->visibility('public')
                    ->maxSize(4096),
                Toggle::make('is_featured')
                    ->label('Featured (home carousel)'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
