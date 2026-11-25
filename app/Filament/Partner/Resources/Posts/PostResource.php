<?php

namespace App\Filament\Partner\Resources\Posts;

use App\Filament\Partner\Resources\Posts\Pages\CreatePost;
use App\Filament\Partner\Resources\Posts\Pages\EditPost;
use App\Filament\Partner\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource as AdminPostResource;

/** Admin PostResource bilan bir xil forma, jadval va scope (hamkor faqat o'z brendlari postlarini ko'radi). */
class PostResource extends AdminPostResource
{
    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
