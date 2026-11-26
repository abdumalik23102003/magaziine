<?php

namespace App\Filament\Partner\Resources\Brands\Schemas;

use App\Models\Brand;
use App\Services\BrandStoryService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
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
                Section::make('Story video')
                    ->description('A scroll-controlled video at the top of your public brand page.')
                    ->columnSpanFull()
                    ->schema([
                        View::make('filament.partner.story-preview'),
                        FileUpload::make('story_video_path')
                            ->label('Video (MP4 or WebM)')
                            ->disk('public')
                            ->directory(fn (Brand $record): string => app(BrandStoryService::class)->directory($record))
                            ->visibility('public')
                            ->acceptedFileTypes(BrandStoryService::MIME_TYPES)
                            ->maxSize(BrandStoryService::MAX_KB)
                            ->previewable(false)
                            ->helperText('Up to 50 MB. Best results: 5-15 seconds, 1280-1920px wide, H.264 MP4 with frequent keyframes. Remove the file and save to delete it.'),
                        Toggle::make('story_enabled')
                            ->label('Show the scroll animation on the public page'),
                        TextInput::make('story_title')
                            ->label('Title over the video')
                            ->maxLength(120),
                        TextInput::make('story_subtitle')
                            ->label('Subtitle over the video')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
