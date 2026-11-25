<?php

namespace App\Filament\Partner\Resources\Brands\Pages;

use App\Filament\Partner\Resources\Brands\BrandProfileResource;
use App\Models\Brand;
use App\Services\PartnerService;
use Filament\Resources\Pages\ListRecords;

class ListBrandProfiles extends ListRecords
{
    protected static string $resource = BrandProfileResource::class;

    /** Bitta brendi bor hamkor ro'yxatni ko'rmay, to'g'ridan-to'g'ri tahrirlashga o'tadi. */
    public function mount(): void
    {
        parent::mount();

        $ids = app(PartnerService::class)->brandIds(auth()->user());

        if (is_array($ids) && count($ids) === 1) {
            $this->redirect(BrandProfileResource::getUrl('edit', ['record' => Brand::query()->findOrFail($ids[0])]));
        }
    }
}
