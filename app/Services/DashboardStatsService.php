<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PostStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Dashboard hisob-kitoblari (widgetda emas, shu yerda). Natija config('dashboard.ttl') soniya keshlanadi. */
class DashboardStatsService
{
    private const KEYS = ['admin', 'moderator', 'operator'];

    public function admin(): array
    {
        return $this->cached('admin', fn () => [
            'users' => User::query()->count(),
            'orders' => Order::query()->count(),
            'sales' => (float) Order::query()->where('status', '!=', OrderStatus::Cancelled->value)->sum('total_price'),
            'queue' => $this->pendingProducts() + $this->pendingPosts(),
            'open_conversations' => $this->openConversations(),
        ]);
    }

    public function moderator(): array
    {
        return $this->cached('moderator', function (): array {
            $since = Carbon::now()->subDays(7);
            $count = fn (array $actions): int => AuditLog::query()->whereIn('action', $actions)->where('created_at', '>=', $since)->count();

            return [
                'pending_products' => $this->pendingProducts(),
                'pending_posts' => $this->pendingPosts(),
                'approved_7d' => $count(['product.approved', 'post.approved']),
                'rejected_7d' => $count(['product.rejected', 'post.rejected']),
            ];
        });
    }

    public function operator(): array
    {
        return $this->cached('operator', function (): array {
            $cfg = config('dashboard.support');
            $unassigned = 0;

            if (Schema::hasTable($cfg['conversations']) && Schema::hasColumn($cfg['conversations'], $cfg['assignee_column'])) {
                $unassigned = DB::table($cfg['conversations'])
                    ->where($cfg['status_column'], $cfg['unassigned_status'])
                    ->whereNull($cfg['assignee_column'])
                    ->count();
            }

            return [
                'open_conversations' => $this->openConversations(),
                'unassigned' => $unassigned,
                'avg_first_response_minutes' => $this->averageFirstResponseMinutes($this->recentMessages()),
            ];
        });
    }

    public function flush(): void
    {
        foreach (self::KEYS as $key) {
            Cache::forget('dashboard.stats.'.$key);
        }
    }

    /**
     * Mijozning birinchi xabaridan operatorning birinchi javobigacha o'rtacha daqiqa.
     * Javobsiz suhbatlar hisobga olinmaydi. Qatorlar: conversation_id, sender_type, created_at.
     */
    public function averageFirstResponseMinutes(iterable $rows): ?float
    {
        $cfg = config('dashboard.support');
        $byConversation = [];

        foreach ($rows as $row) {
            $byConversation[$row->conversation_id][] = [$row->sender_type, Carbon::parse($row->created_at)->getTimestamp()];
        }

        $seconds = [];

        foreach ($byConversation as $messages) {
            $customer = collect($messages)->where(0, $cfg['customer'])->min(1);

            if ($customer === null) {
                continue;
            }

            $reply = collect($messages)->where(0, $cfg['operator'])->filter(fn (array $m): bool => $m[1] >= $customer)->min(1);

            if ($reply !== null) {
                $seconds[] = $reply - $customer;
            }
        }

        return $seconds === [] ? null : round(array_sum($seconds) / count($seconds) / 60, 1);
    }

    private function recentMessages(): iterable
    {
        $cfg = config('dashboard.support');

        if (! Schema::hasTable($cfg['conversations']) || ! Schema::hasTable($cfg['messages'])) {
            return [];
        }

        $ids = DB::table($cfg['conversations'])->orderByDesc('id')->limit($cfg['window'])->pluck('id');

        return DB::table($cfg['messages'])
            ->whereIn($cfg['message_fk'], $ids)
            ->whereIn($cfg['sender_column'], [$cfg['customer'], $cfg['operator']])
            ->get([$cfg['message_fk'].' as conversation_id', $cfg['sender_column'].' as sender_type', 'created_at']);
    }

    private function openConversations(): int
    {
        $cfg = config('dashboard.support');

        return Schema::hasTable($cfg['conversations'])
            ? DB::table($cfg['conversations'])->whereIn($cfg['status_column'], $cfg['open_statuses'])->count()
            : 0;
    }

    private function pendingProducts(): int
    {
        return Product::query()->where('approval_status', 'pending')->count();
    }

    private function pendingPosts(): int
    {
        return Post::query()->where('status', PostStatus::Pending->value)->count();
    }

    private function cached(string $key, Closure $callback): array
    {
        return Cache::remember('dashboard.stats.'.$key, (int) config('dashboard.ttl', 60), $callback);
    }
}
