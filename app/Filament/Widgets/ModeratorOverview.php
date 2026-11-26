<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Services\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ModeratorOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::Moderator);
    }

    protected function getStats(): array
    {
        $s = app(DashboardStatsService::class)->moderator();

        return [
            Stat::make('Pending products', $s['pending_products']),
            Stat::make('Pending posts', $s['pending_posts']),
            Stat::make('Approved, last 7 days', $s['approved_7d']),
            Stat::make('Rejected, last 7 days', $s['rejected_7d']),
        ];
    }
}
