<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_roles_open_the_admin_panel(): void
    {
        foreach (['admin', 'moderator', 'operator'] as $state) {
            $this->actingAs(User::factory()->{$state}()->create())->get('/admin')->assertOk();
        }
    }

    public function test_partner_and_customer_cannot_open_the_admin_panel(): void
    {
        foreach ([User::factory()->partner()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user)->get('/admin')->assertForbidden();
        }
    }

    public function test_inactive_admin_is_locked_out_of_the_panel(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->get('/admin')
            ->assertForbidden();
    }

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

        $ownProduct = (new Product)->forceFill(['brand_id' => $own->id]);
        $otherProduct = (new Product)->forceFill(['brand_id' => $other->id]);

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('update', $otherProduct));
        $this->assertTrue($admin->can('update', new Order));

        $this->assertTrue($moderator->can('viewAny', Product::class));
        $this->assertTrue($moderator->can('approve', $otherProduct));
        $this->assertFalse($moderator->can('update', $ownProduct));
        $this->assertFalse($moderator->can('viewAny', Order::class));
        $this->assertFalse($moderator->can('viewAny', User::class));

        $this->assertTrue($operator->can('viewAny', Order::class));
        $this->assertTrue($operator->can('update', new Order));
        $this->assertFalse($operator->can('viewAny', Product::class));
        $this->assertFalse($operator->can('viewAny', User::class));

        $this->assertTrue($partner->can('create', Product::class));
        $this->assertTrue($partner->can('update', $ownProduct));
        $this->assertFalse($partner->can('update', $otherProduct));
        $this->assertTrue($partner->can('update', $own));
        $this->assertFalse($partner->can('update', $other));
        $this->assertFalse($partner->can('approve', $ownProduct));
        $this->assertFalse($partner->can('viewAny', Order::class));
        $this->assertFalse($partner->can('viewAny', User::class));

        $this->assertFalse($customer->can('viewAny', Product::class));
        $this->assertFalse($customer->can('viewAny', Order::class));
        $this->assertFalse($customer->can('create', Product::class));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'off@example.test']);

        $this->post('/login', ['email' => 'off@example.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertTrue($user->fresh()->role === UserRole::Customer);
    }

    public function test_logged_in_user_is_kicked_out_when_deactivated(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/profile')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'X',
            'email' => 'x@example.test',
            'password' => 'secret-pass-1',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::Customer, $user->fresh()->role);
    }

    public function test_only_admin_opens_the_users_admin_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/create')->assertOk();

        $this->actingAs(User::factory()->moderator()->create());
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_change_role_and_attach_brands(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create();
        $brand = Brand::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => UserRole::Partner->value, 'brands' => [$brand->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame(UserRole::Partner, $user->role);
        $this->assertTrue($user->brands->contains($brand));
    }

    public function test_admin_cannot_demote_or_disable_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['role' => UserRole::Customer->value, 'is_active' => false])
            ->call('save');

        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }
}
