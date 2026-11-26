<?php

namespace App\Models;

use App\Observers\BrandObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(BrandObserver::class)]
class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'cover_path',
        'tagline',
        'description',
        'website',
        'is_featured',
        'is_active',
        'sort_order',
        'story_video_path',
        'story_enabled',
        'story_title',
        'story_subtitle',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'story_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /** products_count: faqat faol va sotuvdagi (faol varianti bor) mahsulotlar. Bitta so'rovda, N+1 siz. */
    public function scopeWithProductsCount(Builder $query): void
    {
        $query->withCount([
            'products as products_count' => fn (Builder $products) => $products->active()->has('activeVariants'),
        ]);
    }

    public static function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'foodmart/')
            ? asset($path)
            : Storage::disk('public')->url($path);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => static::mediaUrl($this->logo_path));
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => static::mediaUrl($this->cover_path));
    }

    /** Faqat http(s) havola. Boshqa sxemalar (javascript: va h.k.) null bo'ladi. */
    protected function websiteUrl(): Attribute
    {
        return Attribute::get(function () {
            $url = trim((string) $this->website);

            return preg_match('~^https?://~i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
        });
    }

    /** Shu brendni boshqaradigan hamkorlar. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
