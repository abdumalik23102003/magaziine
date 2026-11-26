<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PostService
    {
        return app(PostService::class);
    }

    // ---------- Public sahifalar ----------

    public function test_blog_index_lists_only_live_posts(): void
    {
        $inactive = Brand::factory()->inactive()->create();

        Post::factory()->published()->create(['title' => 'Live Story']);
        Post::factory()->create(['title' => 'Secret Draft']);
        Post::factory()->pending()->create(['title' => 'Waiting Story']);
        Post::factory()->rejected()->create(['title' => 'Refused Story']);
        Post::factory()->published()->create(['title' => 'Future Story', 'published_at' => now()->addDay()]);
        Post::factory()->published()->forBrand($inactive)->create(['title' => 'Dormant Brand Story']);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Live Story')
            ->assertDontSee('Secret Draft')
            ->assertDontSee('Waiting Story')
            ->assertDontSee('Refused Story')
            ->assertDontSee('Future Story')
            ->assertDontSee('Dormant Brand Story')
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_blog_index_is_paginated_and_newest_first(): void
    {
        foreach (range(1, 10) as $i) {
            Post::factory()->published()->create(['title' => "Story {$i}", 'published_at' => now()->subDays(20 - $i)]);
        }

        $this->get('/blog')
            ->assertOk()
            ->assertViewHas('posts', fn ($posts) => $posts->count() === 9
                && $posts->total() === 10
                && $posts->items()[0]['title'] === 'Story 10')
            ->assertSee('page=2', false);

        $this->get('/blog?page=2')->assertViewHas('posts', fn ($posts) => $posts->count() === 1);
        $this->get('/blog?page=abc')->assertOk();
    }

    public function test_post_page_renders_markdown_and_json_ld(): void
    {
        $brand = Brand::factory()->create(['name' => 'Orchard Co', 'slug' => 'orchard-co']);

        Post::factory()->published()->forBrand($brand)->create([
            'title' => 'Apple Season',
            'slug' => 'apple-season',
            'body' => 'Hello **crisp** world.

## Picking',
        ]);

        $this->get('/blog/apple-season')
            ->assertOk()
            ->assertSee('Apple Season')
            ->assertSee('<strong>crisp</strong>', false)
            ->assertSee('<h2>Picking</h2>', false)
            ->assertSee('Orchard Co')
            ->assertSee(route('brands.show', 'orchard-co'), false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_post_body_cannot_inject_html_or_unsafe_links(): void
    {
        Post::factory()->published()->create([
            'slug' => 'sneaky',
            'body' => "<script>alert('xss')</script>

<img src=x onerror=alert(1)>

[click](javascript:alert(2))",
        ]);

        $this->get('/blog/sneaky')
            ->assertOk()
            ->assertDontSee("<script>alert('xss')", false)
            ->assertDontSee('onerror', false)
            ->assertDontSee('javascript:alert', false);
    }

    public function test_post_title_is_escaped_in_json_ld(): void
    {
        Post::factory()->published()->create(['slug' => 'tagged', 'title' => 'Break </script><b>out']);

        $this->get('/blog/tagged')
            ->assertOk()
            ->assertDontSee('</script><b>', false);
    }

    public function test_non_live_posts_are_404(): void
    {
        $inactive = Brand::factory()->inactive()->create();

        Post::factory()->create(['slug' => 'draft']);
        Post::factory()->pending()->create(['slug' => 'pending']);
        Post::factory()->rejected()->create(['slug' => 'rejected']);
        Post::factory()->published()->create(['slug' => 'future', 'published_at' => now()->addDay()]);
        Post::factory()->published()->forBrand($inactive)->create(['slug' => 'dormant']);

        foreach (['draft', 'pending', 'rejected', 'future', 'dormant', 'missing'] as $slug) {
            $this->get("/blog/{$slug}")->assertNotFound();
        }
    }

    public function test_post_page_shows_related_posts_of_the_same_brand(): void
    {
        $brand = Brand::factory()->create();
        $other = Brand::factory()->create();

        $main = Post::factory()->published()->forBrand($brand)->create(['slug' => 'main']);
        Post::factory()->published()->forBrand($brand)->create(['title' => 'Sibling Story']);
        Post::factory()->published()->forBrand($other)->create(['title' => 'Stranger Story']);

        $this->get('/blog/main')
            ->assertOk()
            ->assertSee('Sibling Story')
            ->assertDontSee('Stranger Story')
            ->assertViewHas('related', fn ($related) => count($related) === 1 && $related[0]['id'] !== $main->id);
    }

    public function test_brand_page_shows_its_latest_three_live_posts(): void
    {
        $brand = Brand::factory()->create(['slug' => 'writer']);
        $other = Brand::factory()->create();

        foreach ([4 => 'Oldest Story', 3 => 'Third Story', 2 => 'Second Story', 1 => 'Newest Story'] as $daysAgo => $title) {
            Post::factory()->published()->forBrand($brand)->create(['title' => $title, 'published_at' => now()->subDays($daysAgo)]);
        }
        Post::factory()->pending()->forBrand($brand)->create(['title' => 'Pending Story']);
        Post::factory()->published()->forBrand($other)->create(['title' => 'Foreign Story']);

        $this->get('/brands/writer')
            ->assertOk()
            ->assertViewHas('posts', fn ($posts) => count($posts) === 3)
            ->assertSee('Latest from '.$brand->name)
            ->assertSee('Newest Story')
            ->assertSee('Third Story')
            ->assertDontSee('Oldest Story')
            ->assertDontSee('Pending Story')
            ->assertDontSee('Foreign Story');
    }

    public function test_brand_page_hides_the_posts_block_without_posts(): void
    {
        Brand::factory()->create(['slug' => 'quiet']);

        $this->get('/brands/quiet')->assertOk()->assertDontSee('Latest from');
    }

    public function test_home_shows_the_latest_live_posts_and_hides_empty_block(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Fresh Reads');

        foreach (range(1, 4) as $i) {
            Post::factory()->published()->create(['title' => "Home Story {$i}", 'published_at' => now()->subDays(10 - $i)]);
        }
        Post::factory()->create(['title' => 'Hidden Draft']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Fresh Reads')
            ->assertSee(route('blog.index'), false)
            ->assertViewHas('posts', fn ($posts) => count($posts) === 3 && $posts[0]['title'] === 'Home Story 4')
            ->assertDontSee('Home Story 1')
            ->assertDontSee('Hidden Draft');
    }

    // ---------- Policy ----------

    public function test_policy_matrix(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $operator = User::factory()->operator()->create();
        $partner = User::factory()->partner()->create();
        $customer = User::factory()->create();

        $own = Brand::factory()->create();
        $other = Brand::factory()->create();
        $partner->brands()->attach($own);

        $ownDraft = Post::factory()->forBrand($own)->make();
        $ownPublished = Post::factory()->published()->forBrand($own)->make();
        $ownPending = Post::factory()->pending()->forBrand($own)->make();
        $ownRejected = Post::factory()->rejected()->forBrand($own)->make();
        $otherDraft = Post::factory()->forBrand($other)->make();

        // admin: hammasi, shu jumladan publish
        foreach (['view', 'update', 'delete', 'approve', 'reject', 'publish'] as $ability) {
            $this->assertTrue($admin->can($ability, $otherDraft), "admin {$ability}");
        }
        $this->assertTrue($admin->can('viewAny', Post::class));
        $this->assertTrue($admin->can('create', Post::class));

        // moderator: qoralamani ko'rmaydi, qolganini tahrirlaydi, approve/reject, publish yo'q
        $this->assertTrue($moderator->can('viewAny', Post::class));
        $this->assertFalse($moderator->can('view', $ownDraft));
        $this->assertFalse($moderator->can('update', $ownDraft));
        $this->assertTrue($moderator->can('view', $ownPending));
        $this->assertTrue($moderator->can('update', $ownPublished));
        $this->assertTrue($moderator->can('approve', $ownPending));
        $this->assertTrue($moderator->can('reject', $ownPending));
        $this->assertFalse($moderator->can('publish', $ownPending));
        $this->assertFalse($moderator->can('create', Post::class));
        $this->assertFalse($moderator->can('delete', $ownPending));

        // partner: faqat o'z brendi, faqat draft/rejected
        $this->assertTrue($partner->can('viewAny', Post::class));
        $this->assertTrue($partner->can('create', Post::class));
        $this->assertTrue($partner->can('view', $ownPublished));
        $this->assertTrue($partner->can('update', $ownDraft));
        $this->assertTrue($partner->can('update', $ownRejected));
        $this->assertTrue($partner->can('delete', $ownDraft));
        $this->assertTrue($partner->can('submitForReview', $ownDraft));
        $this->assertTrue($partner->can('submitForReview', $ownRejected));
        $this->assertFalse($partner->can('update', $ownPending));
        $this->assertFalse($partner->can('update', $ownPublished));
        $this->assertFalse($partner->can('delete', $ownPublished));
        $this->assertFalse($partner->can('submitForReview', $ownPublished));
        $this->assertFalse($partner->can('view', $otherDraft));
        $this->assertFalse($partner->can('update', $otherDraft));
        $this->assertFalse($partner->can('approve', $ownPending));
        $this->assertFalse($partner->can('reject', $ownPending));
        $this->assertFalse($partner->can('publish', $ownDraft));

        // operator va customer: hech narsa
        foreach ([$operator, $customer] as $user) {
            $this->assertFalse($user->can('viewAny', Post::class));
            $this->assertFalse($user->can('create', Post::class));
            $this->assertFalse($user->can('update', $ownPublished));
        }
    }

    public function test_inactive_admin_has_no_post_permissions(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $this->assertFalse($admin->can('viewAny', Post::class));
        $this->assertFalse($admin->can('publish', Post::factory()->make()));
    }

    // ---------- Servis: holat o'tishlari ----------

    public function test_review_flow_draft_to_pending_to_published(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->create();

        $this->service()->submit($post);
        $this->assertSame(PostStatus::Pending, $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);

        $this->service()->approve($post, $moderator);

        $post->refresh();
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame($moderator->id, $post->reviewed_by);
        $this->assertTrue($post->isLive());
    }

    public function test_rejection_needs_a_reason_and_can_be_resubmitted(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->pending()->create();

        try {
            $this->service()->reject($post, $moderator, '   ');
            $this->fail('A blank reason must be refused.');
        } catch (DomainException) {
            $this->assertSame(PostStatus::Pending, $post->fresh()->status);
        }

        $this->service()->reject($post, $moderator, 'Add a photo.');

        $post->refresh();
        $this->assertSame(PostStatus::Rejected, $post->status);
        $this->assertSame('Add a photo.', $post->rejection_reason);

        $this->service()->submit($post);

        $post->refresh();
        $this->assertSame(PostStatus::Pending, $post->status);
        $this->assertNull($post->rejection_reason);
    }

    public function test_invalid_transitions_are_refused(): void
    {
        $moderator = User::factory()->moderator()->create();
        $admin = User::factory()->admin()->create();

        $draft = Post::factory()->create();
        $published = Post::factory()->published()->create();
        $pending = Post::factory()->pending()->create();

        foreach ([
            fn () => $this->service()->approve($draft, $moderator),
            fn () => $this->service()->reject($draft, $moderator, 'No'),
            fn () => $this->service()->submit($published),
            fn () => $this->service()->submit($pending),
            fn () => $this->service()->publish($published, $admin),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Transition should have been refused.');
            } catch (DomainException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(PostStatus::Draft, $draft->fresh()->status);
    }

    public function test_admin_can_publish_directly(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $this->service()->publish($post, $admin);

        $this->assertTrue($post->fresh()->isLive());
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $post = Post::create([
            'title' => 'Mass',
            'slug' => 'mass',
            'body' => 'x',
            'status' => 'published',
            'user_id' => 99,
            'published_at' => now(),
        ]);

        $post->refresh();
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->user_id);
        $this->assertNull($post->published_at);
    }

    public function test_replacing_a_cover_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/covers/old.png', 'x');
        Storage::disk('public')->put('posts/covers/new.png', 'y');

        $post = Post::factory()->create(['cover_path' => 'posts/covers/old.png']);
        $post->update(['cover_path' => 'posts/covers/new.png']);

        Storage::disk('public')->assertMissing('posts/covers/old.png');
        Storage::disk('public')->assertExists('posts/covers/new.png');

        $post->delete();

        Storage::disk('public')->assertMissing('posts/covers/new.png');
    }

    // ---------- Admin panel ----------

    public function test_admin_and_moderator_open_the_posts_admin(): void
    {
        $post = Post::factory()->pending()->create();

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/posts')->assertOk();
        $this->get('/admin/posts/create')->assertOk();
        $this->get("/admin/posts/{$post->getRouteKey()}/edit")->assertOk();

        $this->actingAs(User::factory()->moderator()->create());
        $this->get('/admin/posts')->assertOk();
        $this->get('/admin/posts/create')->assertForbidden();
        $this->get("/admin/posts/{$post->getRouteKey()}/edit")->assertOk();
    }

    public function test_moderator_cannot_open_a_draft(): void
    {
        $draft = Post::factory()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->get("/admin/posts/{$draft->getRouteKey()}/edit")
            ->assertNotFound();
    }

    public function test_operator_partner_and_customer_cannot_open_the_posts_admin(): void
    {
        foreach ([User::factory()->operator()->create(), User::factory()->partner()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user)->get('/admin/posts')->assertForbidden();
        }
    }

    public function test_admin_creates_a_draft_owned_by_them(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm(['title' => 'Fresh Post', 'slug' => 'fresh-post', 'body' => 'Some **body**.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'fresh-post')->firstOrFail();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame($admin->id, $post->user_id);
        $this->assertNull($post->brand_id);
    }

    public function test_moderator_approves_and_rejects_from_the_edit_page(): void
    {
        $moderator = User::factory()->moderator()->create();
        $this->actingAs($moderator);

        $toApprove = Post::factory()->pending()->create();
        Livewire::test(EditPost::class, ['record' => $toApprove->getRouteKey()])
            ->callAction('approve');

        $this->assertTrue($toApprove->fresh()->isLive());
        $this->assertSame($moderator->id, $toApprove->fresh()->reviewed_by);

        $toReject = Post::factory()->pending()->create();
        Livewire::test(EditPost::class, ['record' => $toReject->getRouteKey()])
            ->callAction('reject', ['reason' => 'Too short.']);

        $toReject->refresh();
        $this->assertSame(PostStatus::Rejected, $toReject->status);
        $this->assertSame('Too short.', $toReject->rejection_reason);
    }

    public function test_moderator_cannot_publish_directly(): void
    {
        $this->actingAs(User::factory()->moderator()->create());

        $post = Post::factory()->pending()->create();

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertActionHidden('publish');

        $this->assertSame(PostStatus::Pending, $post->fresh()->status);
    }
}
