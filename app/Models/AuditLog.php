<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Faqat qo'shiladigan jurnal: tahrirlash va o'chirish bloklangan. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'old_values', 'new_values', 'ip'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('The audit log is append-only.'));
        static::deleting(fn () => throw new LogicException('The audit log is append-only.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Ro'yxatda ko'rinadigan farq: "status: pending -> approved". */
    public function diffText(): string
    {
        $old = (array) $this->old_values;
        $new = (array) $this->new_values;

        return collect(array_unique([...array_keys($old), ...array_keys($new)]))
            ->map(fn ($key) => $key.': '.$this->show($old[$key] ?? null).' -> '.$this->show($new[$key] ?? null))
            ->implode('; ');
    }

    private function show(mixed $value): string
    {
        return $value === null ? '-' : (is_scalar($value) ? (string) (is_bool($value) ? ($value ? 'yes' : 'no') : $value) : json_encode($value));
    }
}
