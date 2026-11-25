<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class PostObserver
{
    /** Muqova almashtirilsa, eskisi diskdan o'chadi. */
    public function updated(Post $post): void
    {
        if ($post->wasChanged('cover_path')) {
            $this->purge($post->getOriginal('cover_path'));
        }
    }

    public function deleted(Post $post): void
    {
        $this->purge($post->cover_path);
    }

    private function purge(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'foodmart/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
