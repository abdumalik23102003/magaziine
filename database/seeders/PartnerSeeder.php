<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\PostStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Ikkita demo hamkor: har biri bitta brendga biriktirilgan, namunaviy mahsulot (pending) va post bilan. */
class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $first = User::query()->where('email', 'partner@magaziine.test')->first()
            ?? User::factory()->partner()->create(['name' => 'Partner', 'email' => 'partner@magaziine.test', 'password' => 'password']);

        $second = User::factory()->partner()->create([
            'name' => 'Partner Two',
            'email' => 'partner2@magaziine.test',
            'password' => 'password',
        ]);

        $category = Category::query()->where('slug', 'pantry')->first() ?? Category::query()->firstOrFail();
        $unit = Unit::query()->where('short_name', 'pcs')->first() ?? Unit::query()->firstOrFail();

        // [hamkor, brend, mahsulot, rasm, post sarlavhasi, post holati]
        $rows = [
            [$first, 'FoodMart Farm', 'Farm Pickled Cucumbers', 'thumb-cucumber.png', 'How we pick cucumbers at dawn', PostStatus::Draft],
            [$second, 'Golden Hive', 'Buckwheat Honey Jar', 'thumb-honey.jpg', 'Three ways to cook with honey', PostStatus::Pending],
        ];

        foreach ($rows as $i => [$partner, $brandName, $productName, $image, $postTitle, $postStatus]) {
            $brand = Brand::query()->where('name', $brandName)->first();

            if (! $brand) {
                continue;
            }

            $partner->brands()->syncWithoutDetaching([$brand->id]);

            $product = new Product([
                'name' => $productName,
                'slug' => Str::slug($productName),
                'description' => $productName.' - added by the partner, waiting for moderation.',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'unit_id' => $unit->id,
                'is_active' => true,
            ]);
            $product->forceFill(['approval_status' => ApprovalStatus::Pending])->save();

            $product->variants()->create([
                'sku' => 'PT-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT).'-1',
                'name' => '500 g',
                'price' => '6.50',
                'discount_percentage' => null,
                'stock' => 40,
                'is_active' => true,
            ]);

            $product->images()->create([
                'path' => 'foodmart/images/'.$image,
                'sort_order' => 0,
                'is_main' => true,
            ]);

            $post = new Post([
                'brand_id' => $brand->id,
                'title' => $postTitle,
                'slug' => Str::slug($postTitle),
                'tag' => 'Partner',
                'excerpt' => 'A short story from our partner brand.',
                'body' => "A first draft written by the partner.\n\n- Fresh\n- Local\n- Seasonal",
                'cover_path' => 'foodmart/images/post-thumb-'.($i + 1).'.jpg',
            ]);
            $post->forceFill(['user_id' => $partner->id, 'status' => $postStatus])->save();
        }
    }
}
