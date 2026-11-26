<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Services\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::Admin);
    }

    protected function getStats(): array
    {
        $s = app(DashboardStatsService::class)->admin();

        return [
            Stat::make('Users', $s['users']),
            Stat::make('Orders', $s['orders']),
            Stat::make('Sales', '$'.number_format($s['sales'], 2))->description('Without cancelled orders'),
            Stat::make('Moderation queue', $s['queue']),
            Stat::make('Open conversations', $s['open_conversations']),
        ];
    }
}
