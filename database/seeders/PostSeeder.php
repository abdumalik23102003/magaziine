<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Demo maqolalar: har holat (published, pending, draft, rejected) va saytning o'zi hamda brendlar nomidan. DatabaseSeeder oxirida chaqiriladi. */
class PostSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@magaziine.test')->first();
        $partner = User::query()->where('email', 'partner@magaziine.test')->first();
        $brands = Brand::query()->pluck('id', 'name');

        $body = fn (string $lead): string => $lead."\n\n"
            ."## Why it matters\n\nFresh produce loses vitamins quickly, so **short supply chains** and smart storage make a real difference.\n\n"
            ."- Buy seasonal\n- Store cold, eat soon\n- Cook gently\n\n"
            .'Read the label, ask where it was grown, and enjoy it at its best.';

        // [sarlavha, tag, excerpt, muqova, brend, muallif, holat, necha kun oldin]
        $posts = [
            ['Top 10 casual fruit bowls for summer', 'Seasonal', 'Citrus, pomegranate and lime: how to build a vitamin-packed bowl in five minutes.', 'post-thumb-1.jpg', null, $admin, PostStatus::Published, 9],
            ['Nut butters worth stocking up on', 'Pantry', 'Cashew, almond or peanut? Our buyers compare taste, texture and protein per spoon.', 'post-thumb-2.jpg', null, $admin, PostStatus::Published, 6],
            ['Why broccoli deserves a weekly slot', 'Nutrition', 'Fibre, vitamin K and a surprisingly fast roast: simple ways to cook it better.', 'post-thumb-3.jpg', null, $admin, PostStatus::Published, 3],
            ['A day in the FoodMart Farm orchards', 'Farm stories', 'Sunrise harvests, hand-picked fruit and the people behind the crates.', 'post-thumb-1.jpg', 'FoodMart Farm', $partner, PostStatus::Published, 5],
            ['How we pick the ripest avocados', 'Tips', 'Three simple checks that tell you when an avocado is ready to eat.', 'post-thumb-2.jpg', 'FoodMart Farm', $partner, PostStatus::Published, 2],
            ['Spring harvest calendar', 'Seasonal', 'What is coming into season over the next eight weeks.', 'post-thumb-3.jpg', 'FoodMart Farm', $partner, PostStatus::Pending, null],
            ['Our packaging pledge', 'Sustainability', 'Draft: less plastic, more compostable crates.', null, 'FoodMart Farm', $partner, PostStatus::Draft, null],
            ['Berries in the mountains', 'Farm stories', 'How Alpine Fresh grows berries above the cloud line.', 'post-thumb-2.jpg', 'Alpine Fresh', null, PostStatus::Published, 4],
            ['Honey tasting notes', 'Pantry', 'Acacia, wildflower and thyme honey side by side.', 'post-thumb-1.jpg', 'Golden Hive', null, PostStatus::Rejected, null],
        ];

        foreach ($posts as [$title, $tag, $excerpt, $cover, $brand, $author, $status, $daysAgo]) {
            $post = new Post([
                'brand_id' => $brand ? $brands[$brand] ?? null : null,
                'title' => $title,
                'slug' => Str::slug($title),
                'tag' => $tag,
                'excerpt' => $excerpt,
                'body' => $body($excerpt),
                'cover_path' => $cover ? 'foodmart/images/'.$cover : null,
            ]);

            $post->forceFill([
                'user_id' => $author?->id,
                'status' => $status,
                'published_at' => $status === PostStatus::Published ? now()->subDays($daysAgo) : null,
                'rejection_reason' => $status === PostStatus::Rejected ? 'Please add the tasting notes and a photo.' : null,
            ])->save();
        }
    }
}
