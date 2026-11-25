<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\UserRole;
use App\Models\Post;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        $imageTypes = ['image/png', 'image/jpeg', 'image/webp'];
        $isPartner = fn (): bool => (bool) auth()->user()?->hasRole(UserRole::Partner);

        return $schema
            ->components([
                TextInput::make('title')
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
                // Hamkor faqat o'z brendlarini tanlaydi va brend majburiy. Admin brendsiz (sayt nomidan) ham yoza oladi.
                Select::make('brand_id')
                    ->label('Brand')
                    ->relationship(
                        'brand',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => auth()->user()?->hasRole(UserRole::Partner)
                            ? $query->whereIn('brands.id', auth()->user()->brands()->select('brands.id'))
                            : $query,
                    )
                    ->searchable()
                    ->preload()
                    ->required($isPartner)
                    ->helperText('Bo\'sh qoldirilsa, maqola saytning o\'z nomidan chiqadi (faqat admin).'),
                TextInput::make('tag')
                    ->maxLength(40)
                    ->placeholder('Seasonal'),
                TextInput::make('excerpt')
                    ->maxLength(300)
                    ->columnSpanFull(),
                MarkdownEditor::make('body')
                    ->required()
                    ->maxLength(50000)
                    ->columnSpanFull(),
                FileUpload::make('cover_path')
                    ->label('Cover image')
                    ->image()
                    ->acceptedFileTypes($imageTypes)
                    ->disk('public')
                    ->directory('posts/covers')
                    ->visibility('public')
                    ->maxSize(4096),
                Textarea::make('rejection_reason')
                    ->label('Moderator feedback')
                    ->rows(3)
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Post $record): bool => filled($record?->rejection_reason))
                    ->columnSpanFull(),
            ]);
    }
}
