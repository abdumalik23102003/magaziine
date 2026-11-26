<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminOverview;
use App\Filament\Widgets\ModeratorOverview;
use App\Filament\Widgets\OperatorOverview;
use App\Models\User;
use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_sees_only_its_own_widget(): void
    {
        $matrix = [
            'admin' => [true, false, false],
            'moderator' => [false, true, false],
            'operator' => [false, false, true],
        ];

        foreach ($matrix as $role => [$admin, $moderator, $operator]) {
            $this->actingAs(User::factory()->{$role}()->create());

            $this->assertSame($admin, AdminOverview::canView(), $role.' admin widget');
            $this->assertSame($moderator, ModeratorOverview::canView(), $role.' moderator widget');
            $this->assertSame($operator, OperatorOverview::canView(), $role.' operator widget');
        }
    }

    public function test_dashboards_render_for_staff(): void
    {
        foreach (['admin', 'moderator', 'operator'] as $role) {
            $this->actingAs(User::factory()->{$role}()->create())->get('/admin')->assertOk();
        }
    }

    public function test_average_first_response_ignores_unanswered_conversations(): void
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        $row = fn (int $c, string $who, string $at) => (object) ['conversation_id' => $c, 'sender_type' => $who, 'created_at' => $at];

        $avg = app(DashboardStatsService::class)->averageFirstResponseMinutes([
            $row(1, 'customer', '2026-01-01 10:00:00'),
            $row(1, 'operator', '2026-01-01 10:10:00'),
            $row(1, 'operator', '2026-01-01 10:50:00'),
            $row(2, 'customer', '2026-01-01 11:00:00'),
            $row(2, 'operator', '2026-01-01 11:20:00'),
            $row(3, 'customer', '2026-01-01 11:30:00'),
        ]);

        $this->assertSame(15.0, $avg);
        $this->assertNull(app(DashboardStatsService::class)->averageFirstResponseMinutes([]));

        Carbon::setTestNow();
    }

    public function test_stats_are_cached_until_flushed(): void
    {
        Cache::flush();
        $service = app(DashboardStatsService::class);

        $before = $service->admin()['users'];
        User::factory()->create();

        $this->assertSame($before, $service->admin()['users']);

        $service->flush();

        $this->assertSame($before + 1, $service->admin()['users']);
    }
}
