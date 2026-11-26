<?php

namespace App\Filament\Partner\Resources\Brands\Pages;

use App\Filament\Partner\Resources\Brands\BrandProfileResource;
use App\Services\BrandStoryService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditBrandProfile extends EditRecord
{
    protected static string $resource = BrandProfileResource::class;

    /** Faqat ruxsat etilgan maydonlar: slug, is_featured, is_active va nomga tegib bo'lmaydi. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data = Arr::only($data, [
            'logo_path', 'cover_path', 'tagline', 'description', 'website',
            'story_video_path', 'story_enabled', 'story_title', 'story_subtitle',
        ]);

        app(BrandStoryService::class)->assertOwnPath($record, $data['story_video_path'] ?? null);

        $record->update($data);

        return $record;
    }
}
