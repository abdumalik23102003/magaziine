<?php

namespace App\Filament\Partner\Resources\Orders\Pages;

use App\Filament\Partner\Resources\Orders\OrderItemResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderItems extends ListRecords
{
    protected static string $resource = OrderItemResource::class;
}
