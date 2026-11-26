<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $product = Product::factory()->create(['slug' => 'apple-box']);
        ProductVariant::factory()->for($product)->create();

        return $product;
    }

    private function commentsUrl(Product $product): string
    {
        return "/products/{$product->slug}/comments";
    }

    public function test_guests_cannot_comment(): void
    {
        $product = $this->product();

        $this->post($this->commentsUrl($product), ['body' => 'Hello'])->assertRedirect();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_ratings_are_stored_and_averaged(): void
    {
        $product = $this->product();

        $this->actingAs(User::factory()->create())->post($this->commentsUrl($product), ['body' => 'Great', 'rating' => 5])->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create())->post($this->commentsUrl($product), ['body' => 'Fine', 'rating' => 4])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame(2, (int) $product->rating_count);
        $this->assertSame(2, (int) $product->comments_count);
        $this->assertEqualsWithDelta(4.5, (float) $product->rating_avg, 0.01);
    }

    public function test_one_rating_per_user_and_range_is_validated(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->post($this->commentsUrl($product), ['body' => 'First', 'rating' => 5]);
        $this->actingAs($user)->post($this->commentsUrl($product), ['body' => 'Again', 'rating' => 1])->assertSessionHasErrors('body');
        $this->actingAs($user)->post($this->commentsUrl($product), ['body' => 'Bad', 'rating' => 9])->assertSessionHasErrors('rating');

        $this->assertSame(1, Comment::count());
        $this->assertSame(1, (int) $product->fresh()->rating_count);
    }

    public function test_replies_nest_without_limit_and_do_not_carry_ratings(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->post($this->commentsUrl($product), ['body' => 'root text', 'rating' => 4]);
        $parent = Comment::firstOrFail();

        foreach (range(1, 8) as $level) {
            $this->actingAs($user)->post($this->commentsUrl($product), [
                'body' => "level {$level}",
                'parent_id' => $parent->id,
                'rating' => 1,
            ])->assertSessionHasNoErrors();

            $parent = Comment::where('body', "level {$level}")->firstOrFail();
            $this->assertNull($parent->rating);
        }

        $product->refresh();
        $this->assertSame(9, (int) $product->comments_count);
        $this->assertSame(1, (int) $product->rating_count);
        $this->assertEqualsWithDelta(4.0, (float) $product->rating_avg, 0.01);

        $this->get("/products/{$product->slug}")->assertOk()->assertSee('level 8')->assertSee('root text');
    }

    public function test_deleting_a_comment_removes_its_replies_and_updates_counters(): void
    {
        $product = $this->product();
        $owner = User::factory()->create();

        $this->actingAs($owner)->post($this->commentsUrl($product), ['body' => 'root', 'rating' => 5]);
        $root = Comment::firstOrFail();
        $this->actingAs($owner)->post($this->commentsUrl($product), ['body' => 'child', 'parent_id' => $root->id]);

        $this->actingAs($owner)->delete(route('comments.destroy', $root))->assertRedirect();

        $this->assertSame(0, Comment::count());
        $product->refresh();
        $this->assertSame(0, (int) $product->comments_count);
        $this->assertSame(0, (int) $product->rating_count);
        $this->assertEqualsWithDelta(0.0, (float) $product->rating_avg, 0.01);
    }

    public function test_only_the_author_or_a_moderator_can_delete(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create())->post($this->commentsUrl($product), ['body' => 'mine']);
        $comment = Comment::firstOrFail();

        $this->actingAs(User::factory()->create())->delete(route('comments.destroy', $comment))->assertForbidden();
        $this->assertSame(1, Comment::count());

        $this->actingAs(User::factory()->moderator()->create())->delete(route('comments.destroy', $comment))->assertRedirect();
        $this->assertSame(0, Comment::count());
    }

    public function test_comment_text_is_escaped(): void
    {
        $product = $this->product();

        $this->actingAs(User::factory()->create())->post($this->commentsUrl($product), ['body' => '<script>alert(1)</script>']);

        $this->get("/products/{$product->slug}")->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_product_views_are_counted_once_per_session(): void
    {
        $product = $this->product();

        $this->get("/products/{$product->slug}")->assertOk();
        $this->assertSame(1, (int) $product->fresh()->views);

        $this->withSession(['viewed_products' => [$product->id]])->get("/products/{$product->slug}")->assertOk();
        $this->assertSame(1, (int) $product->fresh()->views);
    }

    public function test_blog_posts_can_be_rated_and_discussed(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'popular']);

        $this->actingAs(User::factory()->create())
            ->post('/blog/popular/comments', ['body' => 'Nice read', 'rating' => 5])
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame(1, (int) $post->rating_count);
        $this->assertSame(1, (int) $post->comments_count);

        $this->get('/blog/popular')->assertOk()->assertSee('Nice read');
        $this->get('/blog')->assertOk();
    }

    public function test_unpublished_posts_cannot_be_commented(): void
    {
        Post::factory()->create(['slug' => 'draft-x']);

        $this->actingAs(User::factory()->create())->post('/blog/draft-x/comments', ['body' => 'Hi'])->assertNotFound();
    }

    public function test_static_pages_exist_and_are_linked_from_the_footer(): void
    {
        $this->product();

        $home = $this->get('/')->assertOk();

        foreach (['refund-policy' => 'Refund Policy', 'delivery-area' => 'Delivery Area', 'privacy-policy' => 'Privacy Policy', 'terms-of-service' => 'Terms of Service', 'security' => 'Security'] as $slug => $title) {
            $this->get('/'.$slug)->assertOk()->assertSee($title);
            $home->assertSee('/'.$slug, false);
        }
    }

    public function test_app_store_section_is_on_the_home_page(): void
    {
        $this->product();

        $this->get('/')->assertOk()->assertSee('Google Play')->assertSee('App Store');
    }
}
