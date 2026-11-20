<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@magaziine.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $units = [
            'kg' => Unit::create(['name' => 'Kilogram', 'short_name' => 'kg']),
            'pcs' => Unit::create(['name' => 'Piece', 'short_name' => 'pcs']),
            'l' => Unit::create(['name' => 'Litre', 'short_name' => 'l']),
        ];

        $brands = [
            'farm' => Brand::create(['name' => 'FoodMart Farm', 'slug' => 'foodmart-farm']),
            'alpine' => Brand::create(['name' => 'Alpine Fresh', 'slug' => 'alpine-fresh']),
        ];

        $categories = [];
        foreach (['Fruits & Veges', 'Dairy & Eggs', 'Beverages', 'Bakery'] as $name) {
            $categories[$name] = Category::create(['name' => $name, 'slug' => Str::slug($name)]);
        }

        // [nom, kategoriya, brend, birlik, rasm, asl narx, chegirma %, variant nomi]
        $catalog = [
            ['Hass Creamy Avocados', 'Fruits & Veges', 'farm', 'pcs', 'thumb-avocado.png', '5.99', 25, 'Pack of 4'],
            ['Sweet Alpine Raspberries', 'Fruits & Veges', 'alpine', 'kg', 'thumb-raspberries.png', '4.50', 15, '6 oz'],
            ['Crisp Farm Cucumbers', 'Fruits & Veges', 'farm', 'kg', 'thumb-cucumber.png', '2.29', null, '1 kg'],
            ['Vine-Ripened Cherry Tomatoes', 'Fruits & Veges', 'farm', 'kg', 'thumb-tomatoes.png', '7.49', 30, '1 kg'],
            ['Organic Fairtrade Bananas', 'Fruits & Veges', 'alpine', 'kg', 'thumb-bananas.png', '1.99', null, '1 kg'],
            ['Farm Fresh Whole Milk', 'Dairy & Eggs', 'farm', 'l', 'thumb-milk.png', '4.20', 17, '2 pack'],
            ['Cold-Pressed Orange Juice', 'Beverages', 'alpine', 'l', 'thumb-orange-juice.png', '4.25', null, '1 l'],
            ['Crunchy Butter Biscuits', 'Bakery', 'farm', 'pcs', 'thumb-biscuits.png', '3.30', 15, '250 g'],
        ];

        foreach ($catalog as $i => [$name, $category, $brand, $unit, $image, $price, $discount, $variant]) {
            $product = Product::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $name.' - fresh and organic.',
                'category_id' => $categories[$category]->id,
                'brand_id' => $brands[$brand]->id,
                'unit_id' => $units[$unit]->id,
                'is_active' => true,
            ]);

            $product->variants()->create([
                'sku' => 'FM-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'name' => $variant,
                'price' => $price,
                'discount_percentage' => $discount,
                'stock' => 100,
                'is_active' => true,
            ]);

            $product->images()->create([
                'path' => 'foodmart/images/'.$image,
                'sort_order' => 0,
                'is_main' => true,
            ]);
        }
    }
}
