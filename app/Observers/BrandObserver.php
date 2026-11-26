<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\BrandService;
use App\Services\BrandStoryService;
use Illuminate\Support\Facades\Storage;

class BrandObserver
{
    public function __construct(private BrandService $brands, private BrandStoryService $stories) {}

    public function saved(Brand $brand): void
    {
        $this->brands->flush();
    }

    /** Rasm yoki video almashtirilsa, eskisi diskdan o'chadi. */
    public function updated(Brand $brand): void
    {
        if ($brand->wasChanged('story_video_path')) {
            $this->stories->delete($brand, $brand->getOriginal('story_video_path'));
        }

        foreach (['logo_path', 'cover_path'] as $column) {
            if ($brand->wasChanged($column)) {
                $this->purge($brand->getOriginal($column));
            }
        }
    }

    public function deleted(Brand $brand): void
    {
        $this->stories->delete($brand, $brand->story_video_path);
        $this->brands->flush();
        $this->purge($brand->logo_path);
        $this->purge($brand->cover_path);
    }

    private function purge(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'foodmart/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
