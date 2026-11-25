<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\Actions\PostActions;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PostActions::submit(),
            PostActions::approve(),
            PostActions::reject(),
            PostActions::publish(),
            DeleteAction::make(),
        ];
    }
}
