<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Support\Staff;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Izohlar, javoblar va 1-5 baholash. Mahsulot ham, post ham (HasComments) shu servis orqali ishlaydi. */
class CommentService
{
    public const PER_PAGE = 10;

    public const MAX_LENGTH = 2000;

    public function add(User $user, Model $item, string $body, ?int $rating = null, ?int $parentId = null): Comment
    {
        $body = trim($body);

        if ($parentId !== null) {
            if (! $item->comments()->whereKey($parentId)->exists()) {
                throw new DomainException('The comment you are replying to no longer exists.');
            }

            // Baho faqat asosiy izohga beriladi, javobga emas.
            $rating = null;
        }

        if ($body === '' && ($rating === null || $parentId !== null)) {
            throw new DomainException('Write a few words first.');
        }

        if ($rating !== null) {
            if ($rating < 1 || $rating > 5) {
                throw new DomainException('Rating must be between 1 and 5.');
            }

            if ($this->hasRated($user, $item)) {
                throw new DomainException('You have already rated this.');
            }
        }

        return DB::transaction(function () use ($user, $item, $body, $rating, $parentId): Comment {
            $comment = new Comment(['body' => mb_substr($body, 0, self::MAX_LENGTH)]);
            $comment->forceFill([
                'commentable_type' => $item->getMorphClass(),
                'commentable_id' => $item->getKey(),
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'rating' => $rating,
            ])->save();

            $this->refresh($item);

            return $comment;
        });
    }

    /** Izohni va uning barcha javoblarini (istalgan chuqurlikda) o'chiradi. */
    public function delete(Comment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            $item = $comment->commentable;

            Comment::query()->whereIn('id', $this->branchIds($comment->id))->delete();

            if ($item) {
                $this->refresh($item);
            }
        });
    }

    public function canDelete(?User $user, Comment $comment): bool
    {
        return $user !== null && ($user->id === $comment->user_id || Staff::isModerator($user));
    }

    public function hasRated(User $user, Model $item): bool
    {
        return $item->comments()
            ->whereNull('parent_id')
            ->whereNotNull('rating')
            ->where('user_id', $user->id)
            ->exists();
    }

    /** comments_count, rating_count, rating_avg ni qayta hisoblaydi (modelning updated_at iga tegmaydi). */
    public function refresh(Model $item): void
    {
        $stats = Comment::query()
            ->where('commentable_type', $item->getMorphClass())
            ->where('commentable_id', $item->getKey())
            ->selectRaw('count(*) as total, count(rating) as rated, avg(rating) as average')
            ->first();

        DB::table($item->getTable())->where($item->getKeyName(), $item->getKey())->update([
            'comments_count' => (int) $stats->total,
            'rating_count' => (int) $stats->rated,
            'rating_avg' => round((float) $stats->average, 2),
        ]);
    }

    /**
     * Sahifa uchun hamma narsa: asosiy izohlar (sahifalangan), ularning butun javob daraxti,
     * baho xulosasi va forma manzili.
     *
     * @return array<string, mixed>
     */
    public function discussion(Model $item, Request $request): array
    {
        $roots = $item->comments()
            ->whereNull('parent_id')
            ->with('user:id,name')
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE, ['*'], 'comments')
            ->withQueryString()
            ->fragment('discussion');

        $children = [];
        $level = $roots->pluck('id')->all();

        while ($level !== []) {
            $batch = Comment::query()
                ->whereIn('parent_id', $level)
                ->with('user:id,name')
                ->oldest()
                ->oldest('id')
                ->get();

            foreach ($batch as $reply) {
                $children[$reply->parent_id][] = $reply;
            }

            $level = $batch->pluck('id')->all();
        }

        $counts = Comment::query()
            ->where('commentable_type', $item->getMorphClass())
            ->where('commentable_id', $item->getKey())
            ->whereNotNull('rating')
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $distribution[$star] = (int) ($counts[$star] ?? 0);
        }

        $user = $request->user();

        return [
            'item' => $item,
            'roots' => $roots,
            'children' => $children,
            'summary' => [
                'average' => (float) $item->rating_avg,
                'count' => (int) $item->rating_count,
                'distribution' => $distribution,
            ],
            'userRated' => $user !== null && $this->hasRated($user, $item),
            'storeRoute' => $item instanceof Product
                ? route('products.comments.store', $item->slug)
                : route('blog.comments.store', $item),
        ];
    }

    /** @return array<int, int> shu izoh va uning barcha avlodlari */
    private function branchIds(int $id): array
    {
        $all = [$id];
        $level = [$id];

        while ($level !== []) {
            $level = Comment::query()->whereIn('parent_id', $level)->pluck('id')->all();
            $all = array_merge($all, $level);
        }

        return $all;
    }
}
