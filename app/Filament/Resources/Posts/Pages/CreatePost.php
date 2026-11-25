<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /** Muallif va status formadan emas, serverdan: har yangi maqola qoralama bo'lib, joriy foydalanuvchiga yoziladi. */
    protected function handleRecordCreation(array $data): Model
    {
        $post = new Post($data);
        $post->forceFill([
            'user_id' => auth()->id(),
            'status' => PostStatus::Draft,
        ])->save();

        return $post;
    }
}
