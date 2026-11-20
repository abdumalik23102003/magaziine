<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(12),

            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),

            'is_active' => true,
        ];
    }
}
