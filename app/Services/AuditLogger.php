<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /** Aktor yo'q bo'lsa (seeder, console) actor_id bo'sh qoladi. Sezgir maydonlar yozilmaydi. */
    public function record(string $action, ?Model $subject = null, ?array $old = null, ?array $new = null, ?User $actor = null): AuditLog
    {
        $actor ??= auth()->user();

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $this->clean($old),
            'new_values' => $this->clean($new),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    private function clean(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $masked = (array) config('audit.masked', []);

        $kept = array_filter(
            $values,
            fn ($key) => ! collect($masked)->contains(fn ($needle) => str_contains(strtolower((string) $key), strtolower($needle))),
            ARRAY_FILTER_USE_KEY,
        );

        return $kept === [] ? null : $kept;
    }
}
