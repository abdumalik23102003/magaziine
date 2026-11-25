<?php

namespace App\Filament\Partner\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrandProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        $imageTypes = ['image/png', 'image/jpeg', 'image/webp'];

        return $schema
            ->components([
                TextInput::make('tagline')
                    ->maxLength(160)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(6)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://example.com'),
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
            ]);
    }
}
