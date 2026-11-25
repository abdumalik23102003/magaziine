<?php

namespace App\Filament\Partner\Resources\Posts\Pages;

use App\Filament\Partner\Resources\Posts\PostResource;
use App\Filament\Resources\Posts\Pages\ListPosts as AdminListPosts;

class ListPosts extends AdminListPosts
{
    protected static string $resource = PostResource::class;
}
