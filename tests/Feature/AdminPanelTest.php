<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => UserRole::Admin])->save();

        return $user;
    }

    public function test_admin_can_open_all_resource_lists(): void
    {
        $this->actingAs($this->admin());

        foreach (['products', 'categories', 'brands', 'units'] as $path) {
            $this->get("/admin/{$path}")->assertOk();
        }
    }

    public function test_non_admin_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_deleting_main_image_promotes_another_one(): void
    {
        $product = Product::factory()->create();

        $main = ProductImage::factory()->for($product)->create([
            'path' => 'products/a.jpg', 'sort_order' => 0, 'is_main' => true,
        ]);
        $other = ProductImage::factory()->for($product)->create([
            'path' => 'products/b.jpg', 'sort_order' => 1, 'is_main' => false,
        ]);

        $main->delete();

        $this->assertTrue($other->fresh()->is_main);
    }

    public function test_category_descendants_are_found(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child->id]);

        $this->assertEqualsCanonicalizing(
            [$child->id, $grandchild->id],
            $root->descendantIds(),
        );
    }
}
