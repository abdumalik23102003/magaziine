<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostService
{
    public const PER_PAGE = 9;

    public const HOME_LIMIT = 3;

    public const BRAND_LIMIT = 3;

    public const RELATED_LIMIT = 3;

    /** /blog: saytda ko'rinadigan maqolalar, sahifalangan (karta massivlari). */
    public function listing(?string $search = null, ?string $brandSlug = null): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->with('brand:id,name,slug')
            ->when($search, function (Builder $query, string $term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(fn (Builder $where) => $where->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
            })
            ->when($brandSlug, fn (Builder $query, string $slug) => $query->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $slug)))
            ->newest()
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Post $post): array => $this->card($post));
    }

    /** Filtr ro'yxati: faqat saytda ko'rinadigan maqolasi bor faol brendlar. */
    public function brandFilters()
    {
        return Brand::query()
            ->where('is_active', true)
            ->whereIn('id', Post::query()->published()->whereNotNull('brand_id')->select('brand_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /** Ko'rishlar soni: bir sessiyada bir marta, updated_at ga tegmaydi. */
    public function recordView(Post $post, Request $request): void
    {
        $seen = (array) $request->session()->get('viewed_posts', []);

        if (in_array($post->id, $seen, true)) {
            return;
        }

        Post::withoutTimestamps(fn () => Post::query()->whereKey($post->id)->increment('views'));
        $request->session()->push('viewed_posts', $post->id);

        $post->setAttribute('views', (int) $post->views + 1)->syncOriginalAttribute('views');
    }

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = self::HOME_LIMIT): array
    {
        return $this->cards(Post::query()->published()->newest()->limit($limit));
    }

    /** Brend sahifasidagi "oxirgi postlar" bloki. */
    public function latestForBrand(Brand $brand, int $limit = self::BRAND_LIMIT): array
    {
        return $this->cards(Post::query()->published()->where('brand_id', $brand->id)->newest()->limit($limit));
    }

    /** Shu brendning boshqa maqolalari; brendi bo'lmasa, umumiy oxirgilar. */
    public function related(Post $post, int $limit = self::RELATED_LIMIT): array
    {
        $query = Post::query()->published()->whereKeyNot($post->id)->newest()->limit($limit);

        if ($post->brand_id !== null) {
            $query->where('brand_id', $post->brand_id);
        }

        return $this->cards($query);
    }

    /** Karta uchun massiv (brand munosabati yuklangan bo'lsa, N+1 bo'lmaydi). */
    public function card(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => $post->excerpt ?: Str::limit(trim(strip_tags((string) Str::markdown((string) $post->body, ['html_input' => 'strip']))), 140),
            'tag' => $post->tag ?: $post->brand?->name ?: 'Journal',
            'cover_url' => $post->cover_url,
            'views' => (int) $post->views,
            'comments_count' => (int) $post->comments_count,
            'rating_avg' => (float) $post->rating_avg,
            'rating_count' => (int) $post->rating_count,
            'date' => $post->published_at?->format('d M Y'),
            'brand' => $post->brand?->name,
            'url' => route('blog.show', $post),
        ];
    }

    /** schema.org Article (JSON-LD). */
    public function schema(Post $post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'url' => route('blog.show', $post),
            'image' => $post->cover_url,
            'description' => $post->excerpt ?: null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => $post->brand?->name ?? config('app.name'),
            ],
        ]);
    }

    /** Hamkor/admin qoralamani yoki rad etilganini ko'rib chiqishga yuboradi. */
    public function submit(Post $post): Post
    {
        $this->expect($post, [PostStatus::Draft, PostStatus::Rejected], 'submitted for review');

        return $this->move($post, PostStatus::Pending, [
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    /** Moderator/admin: pending -> published. */
    public function approve(Post $post, User $reviewer): Post
    {
        $this->expect($post, [PostStatus::Pending], 'approved');

        return $this->move($post, PostStatus::Published, [
            'published_at' => $post->published_at ?? now(),
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /** Moderator/admin: pending -> rejected (sabab majburiy). */
    public function reject(Post $post, User $reviewer, string $reason): Post
    {
        $this->expect($post, [PostStatus::Pending], 'rejected');

        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A rejection reason is required.');
        }

        return $this->move($post, PostStatus::Rejected, [
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => Str::limit($reason, 1000, ''),
        ]);
    }

    /** Admin: moderatsiyasiz to'g'ridan-to'g'ri e'lon qilish (draft/pending/rejected -> published). */
    public function publish(Post $post, User $admin): Post
    {
        $this->expect($post, [PostStatus::Draft, PostStatus::Pending, PostStatus::Rejected], 'published');

        return $this->move($post, PostStatus::Published, [
            'published_at' => $post->published_at ?? now(),
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /** @param  array<int, PostStatus>  $allowed */
    private function expect(Post $post, array $allowed, string $action): void
    {
        if (! in_array($post->status, $allowed, true)) {
            throw new DomainException("A {$post->status->value} post cannot be {$action}.");
        }
    }

    /** @param  array<string, mixed>  $attributes */
    private function move(Post $post, PostStatus $status, array $attributes): Post
    {
        $post->forceFill(['status' => $status] + $attributes)->save();

        return $post;
    }

    /** @return array<int, array<string, mixed>> */
    private function cards($query): array
    {
        return $query->with('brand:id,name,slug')->get()->map(fn (Post $post): array => $this->card($post))->all();
    }
}
