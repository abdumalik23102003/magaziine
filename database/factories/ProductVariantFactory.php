<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-#####'),
            'name' => fake()->randomElement(['500 g', '1 kg', '2 kg', '1 pcs']),
            'price' => fake()->randomFloat(2, 1, 50),
            'discount_percentage' => fake()->optional(0.3)->numberBetween(5, 40),
            'stock' => fake()->randomFloat(3, 0, 200),
            'is_active' => true,
        ];
    }
}
