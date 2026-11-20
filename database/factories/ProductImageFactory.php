<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'foodmart/images/thumb-avocado.png',
            'sort_order' => 0,
            'is_main' => false,
        ];
    }

    public function main(): static
    {
        return $this->state(fn () => ['is_main' => true]);
    }
}
