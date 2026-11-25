<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    /**
     * Admin hammasini ko'radi. Moderator qoralamalarni ko'rmaydi (faqat review ga yuborilganlarni).
     * Hamkor faqat o'z brendlari maqolalarini ko'radi. Policy bilan bir xil qoida.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['brand', 'author']);
        $user = auth()->user();

        if ($user?->hasRole(UserRole::Partner)) {
            $query->whereIn('brand_id', $user->brands()->select('brands.id'));
        } elseif ($user?->hasRole(UserRole::Moderator)) {
            $query->where('status', '!=', PostStatus::Draft->value);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
