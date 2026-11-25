<?php

namespace App\Filament\Partner\Resources\Brands\Pages;

use App\Filament\Partner\Resources\Brands\BrandProfileResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditBrandProfile extends EditRecord
{
    protected static string $resource = BrandProfileResource::class;

    /** Faqat ruxsat etilgan maydonlar: slug, is_featured, is_active va nomga tegib bo'lmaydi. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update(Arr::only($data, ['logo_path', 'cover_path', 'tagline', 'description', 'website']));

        return $record;
    }
}
