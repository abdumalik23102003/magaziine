<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'slug', 'icon'];

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

    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::get(
            fn () => asset('foodmart/images/'.($this->icon ?: 'icon-vegetables-broccoli.png'))
        );
    }

    /** Hamma avlodlar (bola, nevara...) id lari. Aylanma bog'lanish bo'lsa ham to'xtaydi. */
    public function descendantIds(): array
    {
        $seen = [$this->getKey()];
        $found = [];
        $frontier = [$this->getKey()];

        while ($frontier !== []) {
            $frontier = static::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->diff($seen)
                ->values()
                ->all();

            $seen = array_merge($seen, $frontier);
            $found = array_merge($found, $frontier);
        }

        return $found;
    }

    /** O'zining va barcha avlodlarining id lari: "Fruits" tanlansa ichidagilar ham chiqadi. */
    public function branchIds(): array
    {
        return [$this->getKey(), ...$this->descendantIds()];
    }

    /** Breadcrumb uchun: eng yuqori ota -> ... -> shu kategoriya. */
    public function trail(): Collection
    {
        $trail = new Collection;
        $node = $this;

        while ($node && ! $trail->contains('id', $node->id)) {
            $trail->prepend($node);
            $node = $node->parent;
        }

        return $trail->values();
    }

    /** Bosh sahifa uchun: asosiy kategoriyalar va ulardagi (avlodlari bilan) faol mahsulotlar soni. */
    public static function rootsWithCounts(): Collection
    {
        $roots = static::query()->roots()->orderBy('name')->get();

        $counts = Product::query()
            ->active()
            ->has('activeVariants')
            ->selectRaw('category_id, COUNT(*) AS total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        foreach ($roots as $root) {
            $root->setAttribute('items_count', (int) $counts->only($root->branchIds())->sum());
        }

        return $roots;
    }
}
