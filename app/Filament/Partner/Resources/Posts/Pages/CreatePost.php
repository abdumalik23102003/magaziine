<?php

namespace App\Filament\Partner\Resources\Posts\Pages;

use App\Filament\Partner\Resources\Posts\PostResource;
use App\Filament\Resources\Posts\Pages\CreatePost as AdminCreatePost;
use App\Services\PartnerService;
use Illuminate\Database\Eloquent\Model;

class CreatePost extends AdminCreatePost
{
    protected static string $resource = PostResource::class;

    /** Muallif va Draft holatini ota klass qo'yadi; bu yerda brend egaligi tekshiriladi. */
    protected function handleRecordCreation(array $data): Model
    {
        app(PartnerService::class)->assertOwnsBrand(auth()->user(), $data['brand_id'] ?? null);

        return parent::handleRecordCreation($data);
    }
}
