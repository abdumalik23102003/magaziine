<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ConversationOpened;
use App\Notifications\ModerationDecision;
use App\Notifications\ModerationSubmitted;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Bildirishnoma xatosi (navbat, mail) approve/reject ni hech qachon bekor qilmaydi: faqat log. */
class ModerationNotificationObserver
{
    public function created(Model $model): void
    {
        $this->safely(function () use ($model): void {
            if ($model instanceof Product && $this->v($model->approval_status) === 'pending') {
                $this->send($this->staff(UserRole::Moderator), ModerationSubmitted::for('product', (string) $model->name));
            } elseif ($model::class === config('audit.conversation_model')) {
                $this->send($this->staff(UserRole::Operator), ConversationOpened::make());
            }
        });
    }

    public function updated(Model $model): void
    {
        $this->safely(function () use ($model): void {
            if ($model instanceof Product) {
                $this->moderation($model, 'approval_status', 'product', (string) $model->name);
            } elseif ($model instanceof Post) {
                $this->moderation($model, 'status', 'post', (string) $model->title);
            }
        });
    }

    private function moderation(Model $model, string $field, string $kind, string $label): void
    {
        if (! $model->wasChanged($field)) {
            return;
        }

        $old = $this->v($model->getOriginal($field));
        $new = $this->v($model->{$field});

        if ($new === 'pending') {
            $this->send($this->staff(UserRole::Moderator), ModerationSubmitted::for($kind, $label));

            return;
        }

        if ($old === 'pending' && in_array($new, ['approved', 'published', 'rejected'], true)) {
            $approved = $new !== 'rejected';

            $this->send($this->owners($model), new ModerationDecision($kind, $label, $approved, $approved ? null : $model->rejection_reason));
        }
    }

    private function staff(UserRole $role): Collection
    {
        return User::query()->where('role', $role->value)->where('is_active', true)->get();
    }

    private function owners(Model $model): Collection
    {
        if ($model instanceof Post) {
            return collect([$model->author])->filter();
        }

        return $model->brand?->users()->where('users.role', UserRole::Partner->value)->where('users.is_active', true)->get() ?? collect();
    }

    private function send(Collection $recipients, object $notification): void
    {
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }
    }

    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::warning('Moderation notification failed: '.$e->getMessage());
        }
    }

    private function v(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
