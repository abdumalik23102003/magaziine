<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductImage> */
class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        static $images = null;

        if ($images === null) {
            $files = glob(
                public_path('foodmart/images/{product-thumb-*,thumb-*}.{jpg,jpeg,png,webp}'),
                GLOB_BRACE
            );

            $images = array_map(
                fn ($file) => 'foodmart/images/' . basename($file),
                $files
            );
        }

        return [
            'product_id' => Product::factory(),
            'path' => fake()->randomElement($images),
            'sort_order' => fake()->numberBetween(0, 5),
            'is_main' => false,
        ];
    }
}
