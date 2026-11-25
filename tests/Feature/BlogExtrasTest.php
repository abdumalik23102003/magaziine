<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogExtrasTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_title_and_excerpt(): void
    {
        Post::factory()->published()->create(['title' => 'Mango Season', 'excerpt' => 'Sweet fruit.']);
        Post::factory()->published()->create(['title' => 'Cold Brew', 'excerpt' => 'A guide to mangoes in drinks.']);
        Post::factory()->published()->create(['title' => 'Oat Bread', 'excerpt' => 'Bakery notes.']);

        $this->get('/blog?q=mango')
            ->assertOk()
            ->assertSee('Mango Season')
            ->assertSee('Cold Brew')
            ->assertDontSee('Oat Bread');
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        Post::factory()->published()->create(['title' => 'Plain Title']);

        $this->get('/blog?q=%25')->assertOk()->assertDontSee('Plain Title')->assertSee('No posts match your search');
    }

    public function test_brand_filter_and_dropdown(): void
    {
        $farm = Brand::factory()->create(['name' => 'Farm Co', 'slug' => 'farm-co']);
        $hive = Brand::factory()->create(['name' => 'Hive Co', 'slug' => 'hive-co']);
        Brand::factory()->create(['name' => 'Silent Co', 'slug' => 'silent-co']);

        Post::factory()->published()->forBrand($farm)->create(['title' => 'Farm Story']);
        Post::factory()->published()->forBrand($hive)->create(['title' => 'Hive Story']);
        Post::factory()->published()->create(['title' => 'Site Story']);

        $this->get('/blog?brand=farm-co')
            ->assertOk()
            ->assertSee('Farm Story')
            ->assertDontSee('Hive Story')
            ->assertDontSee('Site Story')
            ->assertSee('Hive Co')
            ->assertDontSee('Silent Co');
    }

    public function test_garbage_query_values_do_not_break_the_page(): void
    {
        Post::factory()->published()->create(['title' => 'Still Here']);

        $this->get('/blog?q[]=x&brand[]=y')->assertOk()->assertSee('Still Here');
        $this->get('/blog?q='.str_repeat('a', 500))->assertOk();
    }

    public function test_pagination_keeps_the_search_query(): void
    {
        foreach (range(1, 10) as $i) {
            Post::factory()->published()->create(['title' => "Fresh Story {$i}"]);
        }

        $this->get('/blog?q=Fresh')->assertOk()->assertSee('q=Fresh');
    }

    public function test_partner_previews_own_draft_but_not_anothers(): void
    {
        $brand = Brand::factory()->create();
        $other = Brand::factory()->create();
        $partner = User::factory()->partner()->create();
        $partner->brands()->attach($brand);

        Post::factory()->forBrand($brand)->create(['slug' => 'my-draft', 'title' => 'My Draft']);
        Post::factory()->forBrand($other)->create(['slug' => 'their-draft']);

        $this->actingAs($partner)->get('/blog/my-draft')
            ->assertOk()
            ->assertSee('My Draft')
            ->assertSee('Preview');

        $this->actingAs($partner)->get('/blog/their-draft')->assertNotFound();
    }

    public function test_guest_and_customer_still_get_404_for_unpublished(): void
    {
        Post::factory()->pending()->create(['slug' => 'queued']);

        $this->get('/blog/queued')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/blog/queued')->assertNotFound();
    }

    public function test_admin_and_moderator_preview_pending_but_moderator_not_drafts(): void
    {
        Post::factory()->pending()->create(['slug' => 'queued']);
        Post::factory()->create(['slug' => 'secret']);

        $this->actingAs(User::factory()->admin()->create())->get('/blog/queued')->assertOk()->assertSee('Preview');
        $this->actingAs(User::factory()->admin()->create())->get('/blog/secret')->assertOk();

        $moderator = User::factory()->moderator()->create();
        $this->actingAs($moderator)->get('/blog/queued')->assertOk();
        $this->actingAs($moderator)->get('/blog/secret')->assertNotFound();
    }

    public function test_rejection_reason_is_shown_in_the_preview(): void
    {
        $brand = Brand::factory()->create();
        $partner = User::factory()->partner()->create();
        $partner->brands()->attach($brand);

        Post::factory()->forBrand($brand)->rejected('Please add sources.')->create(['slug' => 'refused']);

        $this->actingAs($partner)->get('/blog/refused')->assertOk()->assertSee('Please add sources.');
    }

    public function test_a_view_is_counted_once_per_session_without_touching_updated_at(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'popular', 'updated_at' => now()->subDays(3)]);
        $before = $post->fresh()->updated_at->timestamp;

        $this->get('/blog/popular')->assertOk()->assertSee('1 view');

        $this->assertSame(1, (int) $post->fresh()->views);
        $this->assertSame($before, $post->fresh()->updated_at->timestamp);

        $this->withSession(['viewed_posts' => [$post->id]])->get('/blog/popular')->assertOk();

        $this->assertSame(1, (int) $post->fresh()->views);
    }

    public function test_previews_are_not_counted(): void
    {
        $post = Post::factory()->create(['slug' => 'draft-post']);

        $this->actingAs(User::factory()->admin()->create())->get('/blog/draft-post')->assertOk();

        $this->assertSame(0, (int) $post->fresh()->views);
    }
}
