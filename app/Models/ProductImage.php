<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'sort_order',
        'is_main',
    ];

    protected static function booted(): void
    {
        static::saved(function (ProductImage $image): void {
            if ($image->is_main) {
                static::query()
                    ->where('product_id', $image->product_id)
                    ->whereKeyNot($image->getKey())
                    ->update(['is_main' => false]);

                return;
            }

            $hasMain = static::query()
                ->where('product_id', $image->product_id)
                ->where('is_main', true)
                ->exists();

            if (! $hasMain) {
                $image->forceFill(['is_main' => true])->saveQuietly();
            }
        });

        static::deleted(function (ProductImage $image): void {
            if (! $image->is_main) {
                return;
            }

            static::query()
                ->where('product_id', $image->product_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
                ?->update(['is_main' => true]);
        });
    }

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(function () {
            if (str_starts_with($this->path, 'foodmart/')) {
                return asset($this->path);
            }

            return Storage::disk('public')->url($this->path);
        });
    }
}
