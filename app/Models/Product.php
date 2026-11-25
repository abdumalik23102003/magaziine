<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'is_active',
    ];

    protected $attributes = [
        'approval_status' => 'approved',
    ];

    /** approval_status, rejection_reason, reviewed_* ataylab $fillable da yo'q: faqat ProductService o'zgartiradi. */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'approval_status' => ApprovalStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('approval_status', ApprovalStatus::Approved->value);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::Approved->value);
    }

    /** Vitrinada ko'rinadimi: faol va moderatsiyadan o'tgan. */
    public function isVisible(): bool
    {
        return $this->is_active && $this->approval_status === ApprovalStatus::Approved;
    }

    /** Karta uchun kerakli hamma relationni oldindan yuklaydi (N+1 bo'lmasin). */
    public function scopeForCard(Builder $query): void
    {
        $query->with(['category', 'mainImage', 'activeVariants']);
    }

    /** Kartada ko'rsatiladigan variant: chegirmadan keyingi eng arzoni. */
    public function cardVariant(): ?ProductVariant
    {
        return $this->activeVariants
            ->sortBy(fn (ProductVariant $variant) => (float) $variant->final_price)
            ->first();
    }
}
