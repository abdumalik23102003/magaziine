<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'slug'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return array<int, int> ids of all nested children, grandchildren, etc. */
    public function descendantIds(): array
    {
        $all = [];
        $level = [$this->getKey()];

        while ($level !== []) {
            $level = static::query()
                ->whereIn('parent_id', $level)
                ->pluck('id')
                ->all();

            $all = [...$all, ...$level];
        }

        return $all;
    }
}
