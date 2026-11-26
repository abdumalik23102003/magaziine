<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Services\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperatorOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::Operator);
    }

    protected function getStats(): array
    {
        $s = app(DashboardStatsService::class)->operator();
        $avg = $s['avg_first_response_minutes'];

        return [
            Stat::make('Open conversations', $s['open_conversations']),
            Stat::make('Unassigned', $s['unassigned']),
            Stat::make('Avg first response', $avg === null ? '-' : $avg.' min'),
        ];
    }
}
