<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
            'role' => UserRole::Admin,
        ]);

        foreach (['moderator', 'operator', 'partner'] as $state) {
            User::factory()->{$state}()->create([
                'name' => ucfirst($state),
                'email' => "{$state}@magaziine.test",
                'password' => 'password',
            ]);
        }

        $units = [
            'kg' => Unit::create(['name' => 'Kilogram', 'short_name' => 'kg']),
            'pcs' => Unit::create(['name' => 'Piece', 'short_name' => 'pcs']),
            'l' => Unit::create(['name' => 'Litre', 'short_name' => 'l']),
        ];

        // [nom, tagline, logo, cover, featured] (rasmlar public/foodmart/images ichida)
        $brandData = [
            ['FoodMart Farm', 'Straight from our own fields', 'thumb-avocado.png', 'slide-1.jpg', true],
            ['Alpine Fresh', 'Mountain-grown berries and crisp greens', 'thumb-raspberries.png', 'slide-2.jpg', true],
            ['Golden Hive', 'Raw honey and slow-baked treats', 'thumb-honey.jpg', 'slide-3.jpg', true],
            ['Green Valley Dairy', 'Farm milk, bottled at dawn', 'thumb-milk.png', 'slide-4.jpg', true],
            ['Sunrise Bakery', 'Baked before the city wakes up', 'thumb-biscuits.png', 'slide-1.jpg', true],
            ['Citrus Grove', 'Cold-pressed sunshine in a bottle', 'thumb-orange-juice.png', 'slide-2.jpg', true],
            ['Ocean Catch', 'Sustainably caught seafood', 'thumb-tuna.jpg', 'slide-3.jpg', false],
        ];

        $brands = [];
        foreach ($brandData as $i => [$name, $tagline, $logo, $cover, $featured]) {
            $brands[$name] = Brand::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'tagline' => $tagline,
                'description' => $name.' works directly with growers and makers so every product arrives fresh, traceable and fairly priced.',
                'website' => 'https://example.com/'.Str::slug($name),
                'logo_path' => 'foodmart/images/'.$logo,
                'cover_path' => 'foodmart/images/'.$cover,
                'is_featured' => $featured,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }

        // Demo hamkor birinchi brendni boshqaradi.
        User::query()->where('email', 'partner@magaziine.test')->first()?->brands()->attach($brands['FoodMart Farm']);

        // Asosiy kategoriyalar (icon: public/foodmart/images ichidagi fayl)
        $categories = [];
        $roots = [
            'Fruits & Veges' => 'icon-vegetables-broccoli.png',
            'Dairy & Eggs' => 'icon-soft-drinks-bottle.png',
            'Beverages' => 'icon-wine-glass-bottle.png',
            'Bakery' => 'icon-bread-baguette.png',
            'Pantry' => 'icon-bread-herb-flour.png',
            'Seafood & Meat' => 'icon-animal-products-drumsticks.png',
        ];
        foreach ($roots as $name => $icon) {
            $categories[$name] = Category::create(['name' => $name, 'slug' => Str::slug($name), 'icon' => $icon]);
        }

        // Ichki kategoriyalar (breadcrumb va filtr daraxtini sinash uchun)
        foreach (['Fresh Vegetables', 'Organic Fruits'] as $name) {
            $categories[$name] = Category::create([
                'parent_id' => $categories['Fruits & Veges']->id,
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }

        // [nom, kategoriya, brend, birlik, rasm, [[variant nomi, narx, chegirma %], ...]]
        $catalog = [
            ['Hass Creamy Avocados', 'Organic Fruits', 'FoodMart Farm', 'pcs', 'thumb-avocado.png', [['4-Pack', '5.99', 25], ['8-Pack', '10.90', 30], ['24 Crate', '29.00', 35]]],
            ['Sweet Alpine Raspberries', 'Organic Fruits', 'Alpine Fresh', 'kg', 'thumb-raspberries.png', [['6 oz', '4.50', 15], ['1 kg', '14.90', null]]],
            ['Organic Fairtrade Bananas', 'Organic Fruits', 'FoodMart Farm', 'kg', 'thumb-bananas.png', [['1 kg', '1.99', null], ['3 kg', '5.40', 10]]],
            ['Alpine Strawberries', 'Organic Fruits', 'Alpine Fresh', 'kg', 'thumb-raspberries.png', [['500 g', '3.79', null]]],
            ['Ripe Banana Bunch', 'Organic Fruits', 'FoodMart Farm', 'kg', 'thumb-bananas.png', [['2 kg', '3.20', 12]]],
            ['Crisp Farm Cucumbers', 'Fresh Vegetables', 'FoodMart Farm', 'kg', 'thumb-cucumber.png', [['1 kg', '2.29', null]]],
            ['Mini Snack Cucumbers', 'Fresh Vegetables', 'Alpine Fresh', 'kg', 'thumb-cucumber.png', [['500 g', '1.99', 10]]],
            ['Vine-Ripened Cherry Tomatoes', 'Fresh Vegetables', 'FoodMart Farm', 'kg', 'thumb-tomatoes.png', [['1 kg', '7.49', 30], ['500 g', '4.20', 30]]],
            ['Heirloom Garden Tomatoes', 'Fresh Vegetables', 'Alpine Fresh', 'kg', 'thumb-tomatoes.png', [['1 kg', '5.90', null]]],
            ['Fresh Herb Bundle', 'Fresh Vegetables', 'FoodMart Farm', 'pcs', 'thumb-herb.jpg', [['1 bunch', '1.49', null]]],
            ['Farm Fresh Whole Milk', 'Dairy & Eggs', 'Green Valley Dairy', 'l', 'thumb-milk.png', [['1 l', '2.10', null], ['2 pack', '4.20', 17]]],
            ['Light Semi-Skimmed Milk', 'Dairy & Eggs', 'Green Valley Dairy', 'l', 'thumb-milk.png', [['1 l', '1.95', null]]],
            ['Barista Oat Drink', 'Dairy & Eggs', 'Green Valley Dairy', 'l', 'thumb-milk.png', [['1 l', '2.80', 10]]],
            ['Cold-Pressed Orange Juice', 'Beverages', 'Citrus Grove', 'l', 'thumb-orange-juice.png', [['1 l', '4.25', null], ['250 ml', '1.60', null]]],
            ['Smooth Fruit Juice Bottle', 'Beverages', 'FoodMart Farm', 'l', 'product-thumb-1.png', [['500 ml', '3.99', null]]],
            ['Sunrise Citrus Blend', 'Beverages', 'Citrus Grove', 'l', 'thumb-orange-juice.png', [['750 ml', '3.40', 20]]],
            ['Crunchy Butter Biscuits', 'Bakery', 'Sunrise Bakery', 'pcs', 'thumb-biscuits.png', [['250 g', '3.30', 15]]],
            ['Oat & Honey Cookies', 'Bakery', 'Sunrise Bakery', 'pcs', 'thumb-biscuits.png', [['300 g', '3.90', null]]],
            ['Almond Shortbread', 'Bakery', 'Golden Hive', 'pcs', 'thumb-biscuits.png', [['200 g', '4.40', 10]]],
            ['Wildflower Raw Honey Jar', 'Pantry', 'Golden Hive', 'pcs', 'product-thumb-11.jpg', [['350 g', '8.90', null], ['700 g', '15.50', 8]]],
            ['Acacia Honey', 'Pantry', 'Golden Hive', 'pcs', 'thumb-honey.jpg', [['400 g', '9.80', null]]],
            ['Dried Herb Blend', 'Pantry', 'FoodMart Farm', 'pcs', 'product-thumb-12.jpg', [['60 g', '5.60', null]]],
            ['Classic Tomato Ketchup', 'Pantry', 'FoodMart Farm', 'pcs', 'product-thumb-2.png', [['500 g', '2.49', 17]]],
            ['Hearty Food Pouch Meal', 'Pantry', 'Alpine Fresh', 'pcs', 'product-thumb-14.jpg', [['350 g', '6.30', null]]],
            ['Wild Caught Tuna Tin', 'Seafood & Meat', 'Ocean Catch', 'pcs', 'product-thumb-13.jpg', [['160 g', '4.15', 15]]],
            ['Smoked Tuna Fillets', 'Seafood & Meat', 'Ocean Catch', 'pcs', 'thumb-tuna.jpg', [['120 g', '5.20', null]]],
        ];

        foreach ($catalog as $i => [$name, $category, $brand, $unit, $image, $variants]) {
            $product = Product::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $name.' - fresh, carefully selected and delivered straight from the source.',
                'category_id' => $categories[$category]->id,
                'brand_id' => $brands[$brand]->id,
                'unit_id' => $units[$unit]->id,
                'is_active' => true,
            ]);

            foreach ($variants as $n => [$variantName, $price, $discount]) {
                $product->variants()->create([
                    'sku' => sprintf('FM-%04d-%d', $i + 1, $n + 1),
                    'name' => $variantName,
                    'price' => $price,
                    'discount_percentage' => $discount,
                    'stock' => 100,
                    'is_active' => true,
                ]);
            }

            $product->images()->create([
                'path' => 'foodmart/images/'.$image,
                'sort_order' => 0,
                'is_main' => true,
            ]);
        }
    }
}
