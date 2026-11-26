<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;

/** Brendning scroll-story videosi: saqlash joyi, egalik tekshiruvi, o'chirish va public sahifa ma'lumoti. */
class BrandStoryService
{
    public const MAX_KB = 51200;

    public const MIME_TYPES = ['video/mp4', 'video/webm'];

    /** Har bir brendning o'z papkasi. */
    public function directory(Brand $brand): string
    {
        return 'brands/videos/'.$brand->getKey();
    }

    public function owns(Brand $brand, ?string $path): bool
    {
        return $path !== null
            && str_starts_with($path, $this->directory($brand).'/')
            && ! str_contains($path, '..');
    }

    /** Forma orqali boshqa brend fayliga yo'l yuborilishini rad etadi. */
    public function assertOwnPath(Brand $brand, mixed $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (! is_string($path) || ! $this->owns($brand, $path)) {
            throw new AuthorizationException('This video does not belong to your brand.');
        }
    }

    /** Faqat shu brend papkasidagi fayl o'chadi. */
    public function delete(Brand $brand, ?string $path): void
    {
        if ($this->owns($brand, $path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Public sahifa uchun. null = bo'lim ko'rsatilmaydi (o'chirilgan, video yo'q yoki fayl diskda yo'q).
     *
     * @return array{video_url:string, poster_url:?string, title:?string, subtitle:?string}|null
     */
    public function forPage(Brand $brand): ?array
    {
        $path = $brand->story_video_path;

        if (! $brand->story_enabled || ! $this->owns($brand, $path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return [
            'video_url' => Brand::mediaUrl($path),
            'poster_url' => $brand->cover_url,
            'title' => $brand->story_title ?: null,
            'subtitle' => $brand->story_subtitle ?: null,
        ];
    }
}
