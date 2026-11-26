<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\PostStatus;
use App\Filament\Pages\ModerationQueue;
use App\Models\Brand;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Services\ModerationQueueService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::factory()->create();
        $this->moderator = User::factory()->moderator()->create();
    }

    private function product(ApprovalStatus $status): Product
    {
        $product = Product::factory()->create(['brand_id' => $this->brand->id]);
        $product->forceFill(['approval_status' => $status])->save();

        return $product;
    }

    private function makePost(PostStatus $status): Post
    {
        $post = Post::factory()->forBrand($this->brand)->create();
        $post->forceFill(['status' => $status])->save();

        return $post;
    }

    public function test_queue_lists_only_pending_products_and_posts(): void
    {
        $pendingProduct = $this->product(ApprovalStatus::Pending);
        $this->product(ApprovalStatus::Approved);
        $this->product(ApprovalStatus::Draft);
        $pendingPost = $this->makePost(PostStatus::Pending);
        $this->makePost(PostStatus::Draft);

        $service = app(ModerationQueueService::class);
        $keys = $service->pending($this->moderator)->pluck('key')->all();

        $this->assertEqualsCanonicalizing(["product:{$pendingProduct->id}", "post:{$pendingPost->id}"], $keys);
        $this->assertSame(2, $service->count($this->moderator));
    }

    public function test_moderator_approves_product_and_post_from_the_queue(): void
    {
        $product = $this->product(ApprovalStatus::Pending);
        $post = $this->makePost(PostStatus::Pending);
        $service = app(ModerationQueueService::class);

        $service->approve('product', $product->id, $this->moderator);
        $service->approve('post', $post->id, $this->moderator);

        $this->assertSame(ApprovalStatus::Approved, $product->fresh()->approval_status);
        $this->assertNotSame(PostStatus::Pending, $post->fresh()->status);
        $this->assertSame(0, $service->count($this->moderator));
    }

    public function test_reject_requires_a_reason(): void
    {
        $product = $this->product(ApprovalStatus::Pending);
        $service = app(ModerationQueueService::class);

        try {
            $service->reject('product', $product->id, $this->moderator, '   ');
            $this->fail('Sabab majburiy bo\'lishi kerak edi');
        } catch (ValidationException) {
            $this->assertSame(ApprovalStatus::Pending, $product->fresh()->approval_status);
        }

        $service->reject('product', $product->id, $this->moderator, 'Rasm xira.');

        $this->assertSame(ApprovalStatus::Rejected, $product->fresh()->approval_status);
        $this->assertSame('Rasm xira.', $product->fresh()->rejection_reason);
    }

    public function test_post_can_be_rejected_with_a_reason(): void
    {
        $post = $this->makePost(PostStatus::Pending);

        app(ModerationQueueService::class)->reject('post', $post->id, $this->moderator, 'Matnda xato bor.');

        $this->assertNotSame(PostStatus::Pending, $post->fresh()->status);
    }

    public function test_non_moderators_cannot_use_the_queue(): void
    {
        $product = $this->product(ApprovalStatus::Pending);
        $service = app(ModerationQueueService::class);

        foreach ([User::factory()->operator()->create(), User::factory()->partner()->create(), User::factory()->create()] as $user) {
            try {
                $service->approve('product', $product->id, $user);
                $this->fail('Ruxsat berilmasligi kerak edi');
            } catch (AuthorizationException) {
                $this->assertSame(0, $service->count($user));
            }
        }

        $this->assertSame(ApprovalStatus::Pending, $product->fresh()->approval_status);
    }

    public function test_already_handled_item_is_not_found(): void
    {
        $product = $this->product(ApprovalStatus::Approved);

        $this->expectException(ModelNotFoundException::class);
        app(ModerationQueueService::class)->approve('product', $product->id, $this->moderator);
    }

    public function test_page_access_follows_roles(): void
    {
        $this->actingAs($this->moderator);
        $this->assertTrue(ModerationQueue::canAccess());

        $this->actingAs(User::factory()->admin()->create());
        $this->assertTrue(ModerationQueue::canAccess());

        $this->actingAs(User::factory()->operator()->create());
        $this->assertFalse(ModerationQueue::canAccess());
    }
}
