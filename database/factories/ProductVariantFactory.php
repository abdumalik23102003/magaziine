<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'name' => fake()->randomElement([
                'Small',
                'Medium',
                'Large',
                '500 g',
                '1 kg',
            ]),
            'price' => fake()->randomFloat(2, 5, 100),
            'discount_percentage' => fake()->randomElement([0, 5, 10, 15, 20]),
            'stock' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
