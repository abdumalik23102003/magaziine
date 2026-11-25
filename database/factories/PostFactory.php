<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'brand_id' => null,
            'user_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'tag' => 'Seasonal',
            'excerpt' => fake()->sentence(10),
            'body' => implode("\n\n", fake()->paragraphs(3)),
            'status' => PostStatus::Draft,
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(['brand_id' => $brand->id]);
    }

    public function published(): static
    {
        return $this->state(['status' => PostStatus::Published, 'published_at' => now()->subDay()]);
    }

    public function pending(): static
    {
        return $this->state(['status' => PostStatus::Pending]);
    }

    public function rejected(string $reason = 'Needs more detail.'): static
    {
        return $this->state(['status' => PostStatus::Rejected, 'rejection_reason' => $reason, 'reviewed_at' => now()]);
    }
}
