<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Katalog va brend sahifasi uchun umumiy saralash va sahifalash sozlamasi. */
final class ProductSorting
{
    public const DEFAULT = 'latest';

    public const PER_PAGE = 12;

    public const OPTIONS = [
        'latest' => 'Newest',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'name' => 'Name: A-Z',
    ];

    /** Chegirmadan keyingi narx (SQL). Narx bo'yicha saralash shu bilan ishlaydi. */
    private const FINAL_PRICE_SQL = 'product_variants.price * (100 - COALESCE(product_variants.discount_percentage, 0)) / 100';

    /** Query-string dan kelgan istalgan qiymatni xavfsiz saralash kalitiga aylantiradi. */
    public static function resolve(mixed $raw): string
    {
        return is_string($raw) && array_key_exists($raw, self::OPTIONS) ? $raw : self::DEFAULT;
    }

    public static function apply(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc', 'price_desc' => $query
                ->withMin('activeVariants as min_final_price', DB::raw(self::FINAL_PRICE_SQL))
                ->orderBy('min_final_price', $sort === 'price_asc' ? 'asc' : 'desc')
                ->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };
    }
}
