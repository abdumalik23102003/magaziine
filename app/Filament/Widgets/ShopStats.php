<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $revenue = Order::query()
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->sum('total_price');

        return [
            Stat::make('Pending orders', Order::query()->where('status', OrderStatus::Pending->value)->count()),
            Stat::make('Revenue', '$'.number_format((float) $revenue, 2))
                ->description('Without cancelled orders'),
            Stat::make('Customers', User::query()->where('is_admin', false)->count()),
            Stat::make('Out of stock variants', ProductVariant::query()->where('is_active', true)->where('stock', '<', 1)->count()),
        ];
    }
}
