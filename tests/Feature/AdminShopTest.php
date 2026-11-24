<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShopTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    public function test_admin_can_open_dashboard_orders_and_customers(): void
    {
        $this->actingAs($this->admin());

        foreach (['', '/orders', '/users'] as $path) {
            $this->get('/admin'.$path)->assertOk();
        }
    }

    public function test_admin_can_open_an_order(): void
    {
        $admin = $this->admin();
        $order = Order::create([
            'user_id' => $admin->id,
            'customer_name' => 'Test',
            'status' => 'pending',
            'total_price' => '10.00',
        ]);

        $this->actingAs($admin)->get("/admin/orders/{$order->id}/edit")->assertOk();
    }

    public function test_customer_cannot_open_orders_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/orders')->assertForbidden();
    }
}
