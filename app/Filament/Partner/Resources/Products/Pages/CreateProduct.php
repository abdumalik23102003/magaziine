<?php

namespace App\Filament\Partner\Resources\Products\Pages;

use App\Filament\Partner\Resources\Products\ProductResource;
use App\Services\ProductService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** Brend egaligi tekshiriladi, mahsulot pending bo'lib yoziladi (approval_status formadan kelmaydi). */
    protected function handleRecordCreation(array $data): Model
    {
        return app(ProductService::class)->createForPartner($data, auth()->user());
    }
}
