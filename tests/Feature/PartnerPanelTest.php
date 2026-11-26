<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\OrderStatus;
use App\Enums\PostStatus;
use App\Filament\Partner\Resources\Brands\Pages\EditBrandProfile;
use App\Filament\Partner\Resources\Orders\Pages\ListOrderItems;
use App\Filament\Partner\Resources\Posts\Pages\CreatePost as PartnerCreatePost;
use App\Filament\Partner\Resources\Posts\Pages\EditPost as PartnerEditPost;
use App\Filament\Partner\Resources\Posts\Pages\ListPosts as PartnerListPosts;
use App\Filament\Partner\Resources\Products\Pages\CreateProduct as PartnerCreateProduct;
use App\Filament\Partner\Resources\Products\Pages\EditProduct as PartnerEditProduct;
use App\Filament\Partner\Resources\Products\Pages\ListProducts as PartnerListProducts;
use App\Filament\Partner\Widgets\PartnerStats;
use App\Filament\Resources\Products\Pages\ListProducts as AdminListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use App\Services\PartnerService;
use App\Services\ProductService;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $partner;

    private Brand $own;

    private Brand $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->own = Brand::factory()->create(['name' => 'Own Farm', 'slug' => 'own-farm']);
        $this->other = Brand::factory()->create(['name' => 'Rival Farm', 'slug' => 'rival-farm']);

        $this->partner = User::factory()->partner()->create();
        $this->partner->brands()->attach($this->own);

        Filament::setCurrentPanel(Filament::getPanel('partner'));
    }

    private function product(Brand $brand, string $name, ApprovalStatus $status = ApprovalStatus::Approved): Product
    {
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
        $product->forceFill(['approval_status' => $status])->save();

        ProductVariant::factory()->for($product)->create([
            'price' => '10.00',
            'discount_percentage' => null,
            'stock' => 100,
            'is_active' => true,
        ]);

        return $product;
    }

    public function test_only_partner_and_admin_open_the_partner_panel(): void
    {
        $this->actingAs($this->partner)->get('/partner')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/partner')->assertOk();

        foreach ([User::factory()->create(), User::factory()->moderator()->create(), User::factory()->operator()->create(), User::factory()->partner()->inactive()->create()] as $user) {
            $this->actingAs($user)->get('/partner')->assertForbidden();
        }
    }

    public function test_product_list_is_limited_to_own_brands(): void
    {
        $mine = $this->product($this->own, 'Own Honey');
        $theirs = $this->product($this->other, 'Rival Jam');

        $this->actingAs($this->partner);

        Livewire::test(PartnerListProducts::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_other_brand_product_cannot_be_opened_by_url(): void
    {
        $mine = $this->product($this->own, 'Own Honey');
        $theirs = $this->product($this->other, 'Rival Jam');

        $this->actingAs($this->partner);

        $this->get("/partner/products/{$mine->id}/edit")->assertOk();
        $this->assertContains($this->get("/partner/products/{$theirs->id}/edit")->status(), [403, 404]);
    }

    public function test_new_partner_product_is_created_as_pending(): void
    {
        $this->actingAs($this->partner);

        Livewire::test(PartnerCreateProduct::class)
            ->fillForm([
                'name' => 'Fresh Pesto',
                'slug' => 'fresh-pesto',
                'category_id' => Category::factory()->create()->id,
                'brand_id' => $this->own->id,
                'unit_id' => Unit::factory()->create()->id,
                'description' => 'Basil and pine nuts.',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'fresh-pesto')->firstOrFail();

        $this->assertSame(ApprovalStatus::Pending, $product->approval_status);
        $this->assertSame($this->own->id, $product->brand_id);
    }

    public function test_partner_cannot_create_a_product_for_another_brand(): void
    {
        $this->actingAs($this->partner);

        try {
            Livewire::test(PartnerCreateProduct::class)
                ->fillForm([
                    'name' => 'Sneaky Jam',
                    'slug' => 'sneaky-jam',
                    'category_id' => Category::factory()->create()->id,
                    'brand_id' => $this->other->id,
                    'unit_id' => Unit::factory()->create()->id,
                ])
                ->call('create');
        } catch (\Throwable) {
            // 403 yoki forma xatosi: ikkalasi ham to'g'ri
        }

        $this->assertDatabaseMissing('products', ['slug' => 'sneaky-jam']);
    }

    public function test_partner_cannot_move_a_product_to_another_brand_on_edit(): void
    {
        $mine = $this->product($this->own, 'Own Honey');

        $this->actingAs($this->partner);

        try {
            Livewire::test(PartnerEditProduct::class, ['record' => $mine->getKey()])
                ->fillForm(['brand_id' => $this->other->id])
                ->call('save');
        } catch (\Throwable) {
        }

        $this->assertSame($this->own->id, $mine->fresh()->brand_id);
    }

    public function test_pending_product_is_hidden_from_the_storefront_until_approved(): void
    {
        $product = $this->product($this->own, 'Pending Pesto', ApprovalStatus::Pending);
        $variant = $product->variants()->first();

        $this->get('/catalog')->assertOk()->assertDontSee('Pending Pesto');
        $this->get('/brands/own-farm')->assertOk()->assertDontSee('Pending Pesto');
        $this->get('/products/pending-pesto')->assertNotFound();
        $this->withSession(['visitor_token' => 'pt'])->postJson('/cart/items', ['variant_id' => $variant->id])->assertStatus(422);
        $this->postJson("/wishlist/{$product->id}")->assertNotFound();

        $product->forceFill(['approval_status' => ApprovalStatus::Approved])->save();

        $this->get('/catalog')->assertSee('Pending Pesto');
        $this->get('/products/pending-pesto')->assertOk();
        $this->withSession(['visitor_token' => 'pt'])->postJson('/cart/items', ['variant_id' => $variant->id])->assertOk();
    }

    public function test_products_default_to_approved_when_created_outside_the_partner_flow(): void
    {
        $product = Product::factory()->create();

        $this->assertSame(ApprovalStatus::Approved, $product->approval_status);
        $this->assertSame(ApprovalStatus::Approved, $product->fresh()->approval_status);
        $this->assertTrue($product->isVisible());
    }

    public function test_changing_the_name_of_an_approved_product_sends_it_back_to_moderation(): void
    {
        $mine = $this->product($this->own, 'Own Honey');

        $this->actingAs($this->partner);

        Livewire::test(PartnerEditProduct::class, ['record' => $mine->getKey()])
            ->fillForm(['name' => 'Own Honey Deluxe'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(ApprovalStatus::Pending, $mine->fresh()->approval_status);
    }

    public function test_reapproval_can_be_switched_off_in_config(): void
    {
        config(['partner.reapprove_on_change' => false]);

        $mine = $this->product($this->own, 'Own Honey');

        $this->actingAs($this->partner);

        Livewire::test(PartnerEditProduct::class, ['record' => $mine->getKey()])
            ->fillForm(['name' => 'Own Honey Deluxe'])
            ->call('save');

        $this->assertSame(ApprovalStatus::Approved, $mine->fresh()->approval_status);
    }

    public function test_images_and_price_follow_the_watch_settings(): void
    {
        $mine = $this->product($this->own, 'Own Honey');
        $variant = $mine->variants()->first();

        $this->actingAs($this->partner);

        $variant->update(['price' => '99.00', 'stock' => 5]);
        $this->assertSame(ApprovalStatus::Approved, $mine->fresh()->approval_status);

        config(['partner.watch.variants' => true]);
        $variant->update(['stock' => 7]);
        $this->assertSame(ApprovalStatus::Approved, $mine->fresh()->approval_status);

        $variant->update(['price' => '120.00']);
        $this->assertSame(ApprovalStatus::Pending, $mine->fresh()->approval_status);

        $other = $this->product($this->own, 'Own Jam');
        ProductImage::factory()->for($other)->create();
        $this->assertSame(ApprovalStatus::Pending, $other->fresh()->approval_status);
    }

    public function test_admin_edits_do_not_trigger_reapproval(): void
    {
        $mine = $this->product($this->own, 'Own Honey');

        $this->actingAs(User::factory()->admin()->create());

        $mine->update(['name' => 'Renamed By Admin']);

        $this->assertSame(ApprovalStatus::Approved, $mine->fresh()->approval_status);
    }

    public function test_partner_cannot_approve_but_moderator_can(): void
    {
        $pending = $this->product($this->own, 'Pending Pesto', ApprovalStatus::Pending);
        $moderator = User::factory()->moderator()->create();

        $this->assertFalse($this->partner->can('approve', $pending));
        $this->assertFalse($this->partner->can('reject', $pending));
        $this->assertTrue($moderator->can('approve', $pending));

        $service = app(ProductService::class);
        $service->approve($pending, $moderator);

        $this->assertSame(ApprovalStatus::Approved, $pending->fresh()->approval_status);
        $this->assertSame($moderator->id, $pending->fresh()->reviewed_by);

        $this->expectException(DomainException::class);
        $service->approve($pending->fresh(), $moderator);
    }

    public function test_rejection_needs_a_reason_and_partner_can_resubmit(): void
    {
        $pending = $this->product($this->own, 'Pending Pesto', ApprovalStatus::Pending);
        $moderator = User::factory()->moderator()->create();
        $service = app(ProductService::class);

        try {
            $service->reject($pending, $moderator, '   ');
            $this->fail('Reason must be required');
        } catch (DomainException) {
        }

        $service->reject($pending, $moderator, 'Photo is blurry.');

        $this->assertSame(ApprovalStatus::Rejected, $pending->fresh()->approval_status);
        $this->assertSame('Photo is blurry.', $pending->fresh()->rejection_reason);
        $this->assertTrue($this->partner->can('submitForReview', $pending->fresh()));

        $service->submit($pending->fresh());

        $this->assertSame(ApprovalStatus::Pending, $pending->fresh()->approval_status);
        $this->assertNull($pending->fresh()->rejection_reason);
    }

    public function test_moderator_can_approve_from_the_admin_table(): void
    {
        $pending = $this->product($this->own, 'Pending Pesto', ApprovalStatus::Pending);

        $this->actingAs(User::factory()->moderator()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AdminListProducts::class)
            ->callTableAction('approve', $pending)
            ->assertHasNoTableActionErrors();

        $this->assertSame(ApprovalStatus::Approved, $pending->fresh()->approval_status);
    }

    public function test_partner_creates_a_draft_post_and_cannot_publish_it(): void
    {
        $this->actingAs($this->partner);

        Livewire::test(PartnerCreatePost::class)
            ->fillForm([
                'title' => 'Our Honey Story',
                'slug' => 'our-honey-story',
                'brand_id' => $this->own->id,
                'body' => "Hello\n\nWorld",
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'our-honey-story')->firstOrFail();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame($this->partner->id, $post->user_id);
        $this->assertSame($this->own->id, $post->brand_id);
        $this->assertFalse($this->partner->can('publish', $post));

        Livewire::test(PartnerEditPost::class, ['record' => $post->getRouteKey()])
            ->assertActionHidden('publish')
            ->callAction('submit');

        $this->assertSame(PostStatus::Pending, $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
        $this->assertFalse($this->partner->can('update', $post->fresh()));
    }

    public function test_partner_cannot_create_a_post_for_another_brand(): void
    {
        $this->actingAs($this->partner);

        try {
            Livewire::test(PartnerCreatePost::class)
                ->fillForm([
                    'title' => 'Sneaky Post',
                    'slug' => 'sneaky-post',
                    'brand_id' => $this->other->id,
                    'body' => 'x',
                ])
                ->call('create');
        } catch (\Throwable) {
        }

        $this->assertDatabaseMissing('posts', ['slug' => 'sneaky-post']);
    }

    public function test_post_list_is_limited_to_own_brands(): void
    {
        $mine = Post::factory()->forBrand($this->own)->create();
        $theirs = Post::factory()->forBrand($this->other)->create();

        $this->actingAs($this->partner);

        Livewire::test(PartnerListPosts::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_brand_profile_is_limited_to_own_brands_and_whitelisted_fields(): void
    {
        $this->actingAs($this->partner);

        $this->get('/partner/brand-profile')
            ->assertRedirect("/partner/brand-profile/{$this->own->getRouteKey()}/edit");

        $this->assertContains($this->get("/partner/brand-profile/{$this->other->getRouteKey()}/edit")->status(), [403, 404]);

        Livewire::test(EditBrandProfile::class, ['record' => $this->own->getRouteKey()])
            ->fillForm(['tagline' => 'Honey with attitude', 'website' => 'https://own.example.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        $own = $this->own->fresh();

        $this->assertSame('Honey with attitude', $own->tagline);
        $this->assertSame('own-farm', $own->slug);
        $this->assertFalse($own->is_featured);
        $this->assertTrue($own->is_active);
    }

    public function test_orders_show_only_own_lines_without_private_customer_data(): void
    {
        $mine = $this->product($this->own, 'Own Honey');
        $theirs = $this->product($this->other, 'Rival Jam');

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'customer_name' => 'Ana Karimova',
            'status' => OrderStatus::Pending,
            'subtotal' => '30.00',
            'total_price' => '30.00',
            'phone' => '+998 90 000 00 00',
            'address' => 'Amir Temur 10, 12, Tashkent',
        ]);

        $ownLine = $order->items()->create([
            'product_variant_id' => $mine->variants()->first()->id,
            'product_name' => 'Own Honey',
            'variant_name' => '1 kg',
            'price' => '10.00',
            'quantity' => 2,
        ]);
        $foreignLine = $order->items()->create([
            'product_variant_id' => $theirs->variants()->first()->id,
            'product_name' => 'Rival Jam',
            'variant_name' => '1 kg',
            'price' => '10.00',
            'quantity' => 1,
        ]);

        $this->actingAs($this->partner);

        Livewire::test(ListOrderItems::class)
            ->assertCanSeeTableRecords([$ownLine])
            ->assertCanNotSeeTableRecords([$foreignLine]);

        $this->get('/partner/orders')
            ->assertOk()
            ->assertSee('Ana Karimova')
            ->assertSee('Tashkent')
            ->assertDontSee('+998 90 000 00 00')
            ->assertDontSee('Amir Temur 10')
            ->assertDontSee('Rival Jam');
    }

    public function test_dashboard_numbers_cover_only_own_products(): void
    {
        $mine = $this->product($this->own, 'Own Honey');
        $theirs = $this->product($this->other, 'Rival Jam');
        $mine->variants()->first()->update(['stock' => 3]);

        $customer = User::factory()->create();
        $make = fn (string $status, $createdAt, ProductVariant $variant, string $name) => tap(
            Order::create(['user_id' => $customer->id, 'customer_name' => 'A', 'status' => $status, 'subtotal' => '0', 'total_price' => '0']),
            function (Order $order) use ($variant, $name, $createdAt): void {
                $order->items()->create(['product_variant_id' => $variant->id, 'product_name' => $name, 'variant_name' => 'x', 'price' => '10.00', 'quantity' => 2]);
                $order->forceFill(['created_at' => $createdAt])->save();
            },
        );

        $make('pending', now()->subDays(2), $mine->variants()->first(), 'Own Honey');
        $make('cancelled', now()->subDays(2), $mine->variants()->first(), 'Own Honey');
        $make('pending', now()->subDays(40), $mine->variants()->first(), 'Own Honey');
        $make('pending', now()->subDays(2), $theirs->variants()->first(), 'Rival Jam');

        $partners = app(PartnerService::class);

        $this->assertSame(20.0, $partners->salesLast30Days($this->partner));
        $this->assertSame(1, $partners->lowStock($this->partner, 10)->count());
        $this->assertSame(1, $partners->products($this->partner)->count());
        $this->assertSame(0, $partners->pendingCount($this->partner));

        $this->actingAs($this->partner)->get('/partner')->assertOk();

        Livewire::withoutLazyLoading()->test(PartnerStats::class)->assertSee('Sales, last 30 days');
    }
}
