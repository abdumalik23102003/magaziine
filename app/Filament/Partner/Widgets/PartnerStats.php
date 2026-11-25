<?php

namespace App\Filament\Partner\Widgets;

use App\Services\PartnerService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PartnerStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $partners = app(PartnerService::class);
        $threshold = (int) config('partner.low_stock_threshold');

        return [
            Stat::make('Products', $partners->products($user)->count()),
            Stat::make('Awaiting moderation', $partners->pendingCount($user)),
            Stat::make('Sales, last 30 days', '$'.number_format($partners->salesLast30Days($user), 2))
                ->description('Without cancelled orders'),
            Stat::make('Low stock variants', $partners->lowStock($user, $threshold)->count())
                ->description("Below {$threshold} in stock"),
        ];
    }
}
