<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Support\Staff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ModerationQueueService
{
    public function __construct(
        private ProductService $products,
        private PostService $posts,
    ) {}

    public function pending(User $actor): Collection
    {
        $this->authorize($actor);

        $products = $this->pendingProducts()->with('brand')->latest('updated_at')->limit(100)->get()
            ->map(fn (Product $p) => $this->item('product', $p, (string) $p->name));

        $posts = $this->pendingPosts()->with('brand')->latest('updated_at')->limit(100)->get()
            ->map(fn (Post $p) => $this->item('post', $p, (string) $p->title));

        return $products->concat($posts)->sortByDesc('updated_at')->values();
    }

    public function count(?User $actor): int
    {
        if (! Staff::isModerator($actor)) {
            return 0;
        }

        return $this->pendingProducts()->count() + $this->pendingPosts()->count();
    }

    public function approve(string $type, int $id, User $actor): void
    {
        $this->authorize($actor);

        $model = $this->resolve($type, $id);
        $type === 'product'
            ? $this->products->approve($model, $actor)
            : $this->posts->approve($model, $actor);
    }

    public function reject(string $type, int $id, User $actor, string $reason): void
    {
        $this->authorize($actor);

        $reason = trim($reason);
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Rad etish sababi majburiy.',
        ])->validate();

        $model = $this->resolve($type, $id);
        $type === 'product'
            ? $this->products->reject($model, $actor, $reason)
            : $this->posts->reject($model, $actor, $reason);
    }

    private function authorize(User $actor): void
    {
        if (! Staff::isModerator($actor)) {
            throw new AuthorizationException('Moderatsiya navbatiga ruxsat yo\'q.');
        }
    }

    private function resolve(string $type, int $id): Model
    {
        return match ($type) {
            'product' => $this->pendingProducts()->findOrFail($id),
            'post' => $this->pendingPosts()->findOrFail($id),
            default => throw new \InvalidArgumentException('Noma\'lum tur.'),
        };
    }

    private function pendingProducts()
    {
        return Product::query()->where($this->column(new Product, ApprovalStatus::class), ApprovalStatus::Pending->value);
    }

    private function pendingPosts()
    {
        return Post::query()->where($this->column(new Post, PostStatus::class), PostStatus::Pending->value);
    }

    private function column(Model $model, string $enum): string
    {
        foreach ($model->getCasts() as $attribute => $cast) {
            if ($cast === $enum) {
                return $attribute;
            }
        }

        throw new \LogicException(class_basename($model).' da '.class_basename($enum).' cast topilmadi.');
    }

    private function item(string $type, Model $model, string $title): array
    {
        $text = (string) ($model->description ?? $model->excerpt ?? '');
        $body = (string) ($model->body ?? '');

        return [
            'key' => $type.':'.$model->getKey(),
            'type' => $type,
            'id' => $model->getKey(),
            'title' => $title,
            'brand' => $model->brand?->name,
            'updated_at' => $model->updated_at,
            'preview' => Str::limit(trim(strip_tags($text."\n\n".$body)), 1500),
        ];
    }
}
