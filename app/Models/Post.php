<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\HasComments;
use App\Observers\PostObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * user_id, status, published_at, reviewed_* ataylab $fillable da yo'q:
 * ular faqat PostService orqali (moderatsiya oqimi bo'yicha) o'zgaradi.
 */
#[ObservedBy(PostObserver::class)]
class Post extends Model
{
    use HasComments, HasFactory;

    protected $fillable = [
        'brand_id',
        'title',
        'slug',
        'tag',
        'excerpt',
        'body',
        'cover_path',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Saytda ko'rinadiganlar: published, vaqti kelgan, brendi (bo'lsa) faol. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published->value)
            ->where('published_at', '<=', now())
            ->where(fn (Builder $where) => $where
                ->whereNull('brand_id')
                ->orWhereHas('brand', fn (Builder $brand) => $brand->where('is_active', true)));
    }

    public function scopeNewest(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /** Public sahifa uchun ham shu qoida (scopePublished bilan bir xil). */
    public function isLive(): bool
    {
        if ($this->status !== PostStatus::Published || $this->published_at === null || $this->published_at->isFuture()) {
            return false;
        }

        return $this->brand_id === null || (bool) $this->brand?->is_active;
    }

    /** Hamkor faqat qoralama yoki rad etilgan maqolani o'zgartira oladi. */
    public function isEditableByPartner(): bool
    {
        return in_array($this->status, [PostStatus::Draft, PostStatus::Rejected], true);
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => Brand::mediaUrl($this->cover_path));
    }

    /** Markdown -> HTML. Xom HTML tashlab yuboriladi, xavfli havolalar (javascript:) o'chiriladi. */
    protected function bodyHtml(): Attribute
    {
        return Attribute::get(fn (): HtmlString => new HtmlString(
            Str::markdown((string) $this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false])
        ));
    }

    protected function readingMinutes(): Attribute
    {
        return Attribute::get(fn (): int => max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200)));
    }
}
