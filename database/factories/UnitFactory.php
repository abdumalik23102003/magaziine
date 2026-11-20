<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    public function definition(): array
    {
        $units = [
            ['name' => 'Kilogram', 'short_name' => 'kg'],
            ['name' => 'Liter', 'short_name' => 'l'],
            ['name' => 'Piece', 'short_name' => 'pc'],
            ['name' => 'Gram', 'short_name' => 'g'],
        ];

        return fake()->randomElement($units);
    }
}
