<?php

namespace App\Filament\Partner\Resources\Posts\Pages;

use App\Filament\Partner\Resources\Posts\PostResource;
use App\Filament\Resources\Posts\Pages\EditPost as AdminEditPost;
use App\Services\PartnerService;
use Illuminate\Database\Eloquent\Model;

class EditPost extends AdminEditPost
{
    protected static string $resource = PostResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(PartnerService::class)->assertOwnsBrand(auth()->user(), $data['brand_id'] ?? null);

        $record->update($data);

        return $record;
    }
}
