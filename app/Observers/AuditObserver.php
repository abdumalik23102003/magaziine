<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogger;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Mavjud servislarga tegmasdan hodisalarni model darajasida yozadi. Audit xatosi asosiy amalni to'xtatmaydi. */
class AuditObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function updated(Model $model): void
    {
        try {
            $this->handle($model);
        } catch (Throwable $e) {
            Log::warning('Audit log failed: '.$e->getMessage(), ['model' => $model::class]);
        }
    }

    private function handle(Model $model): void
    {
        if ($model instanceof User) {
            if ($model->wasChanged('role')) {
                $this->audit->record('role.changed', $model, ['role' => $this->v($model->getOriginal('role'))], ['role' => $this->v($model->role)]);
            }

            if ($model->wasChanged('is_active')) {
                $this->audit->record($model->is_active ? 'user.activated' : 'user.deactivated', $model, ['is_active' => ! $model->is_active], ['is_active' => (bool) $model->is_active]);
            }

            return;
        }

        if ($model instanceof Order) {
            if ($model->wasChanged('status')) {
                $this->audit->record('order.status_changed', $model, ['status' => $this->v($model->getOriginal('status'))], ['status' => $this->v($model->status)]);
            }

            return;
        }

        if ($model instanceof Product) {
            $this->moderation($model, 'product', 'approval_status', $model->name);

            return;
        }

        if ($model instanceof Post) {
            $this->moderation($model, 'post', 'status', $model->title);

            return;
        }

        foreach ((array) config('audit.transfer_attributes', []) as $attribute) {
            if ($model->wasChanged($attribute)) {
                $this->audit->record('conversation.transferred', $model, [$attribute => $model->getOriginal($attribute)], [$attribute => $model->{$attribute}]);

                return;
            }
        }
    }

    private function moderation(Model $model, string $kind, string $field, ?string $label): void
    {
        if (! $model->wasChanged($field)) {
            return;
        }

        $old = $this->v($model->getOriginal($field));
        $new = $this->v($model->{$field});

        $action = match ($new) {
            'approved', 'published' => $old === 'pending' ? "{$kind}.approved" : "{$kind}.published",
            'rejected' => "{$kind}.rejected",
            default => null,
        };

        if ($action === null) {
            return;
        }

        $this->audit->record(
            $action,
            $model,
            [$field => $old],
            array_filter([$field => $new, 'label' => $label, 'reason' => $new === 'rejected' ? $model->rejection_reason : null]),
        );
    }

    private function v(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
