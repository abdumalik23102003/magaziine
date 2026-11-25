<?php

namespace App\Filament\Partner\Resources\Products\Pages;

use App\Filament\Partner\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Actions\ProductActions;
use App\Services\PartnerService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /** brand_id ni boshqa brendga almashtirib bo'lmaydi. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(PartnerService::class)->assertOwnsBrand(auth()->user(), $data['brand_id'] ?? null);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ProductActions::submit(),
            DeleteAction::make(),
        ];
    }
}
