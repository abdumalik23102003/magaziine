<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ModerationDecision;
use App\Notifications\ModerationSubmitted;
use App\Services\AuditLogger;
use App\Services\PostService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

class AuditNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function pendingProduct(User $partner): Product
    {
        $brand = Brand::factory()->create();
        $partner->brands()->attach($brand);
        $product = Product::factory()->create(['brand_id' => $brand->id]);
        $product->forceFill(['approval_status' => ApprovalStatus::Pending])->save();

        return $product;
    }

    public function test_role_and_activation_changes_are_audited_with_the_actor(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);
        $user->forceFill(['role' => UserRole::Moderator])->save();
        $user->forceFill(['is_active' => false])->save();
        $user->forceFill(['is_active' => true])->save();

        $log = AuditLog::query()->where('action', 'role.changed')->firstOrFail();

        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame(['role' => 'customer'], $log->old_values);
        $this->assertSame(['role' => 'moderator'], $log->new_values);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated', 'subject_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.activated', 'subject_id' => $user->id]);
    }

    public function test_changes_without_an_actor_are_supported(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => UserRole::Operator])->save();

        $this->assertNull(AuditLog::query()->where('action', 'role.changed')->firstOrFail()->actor_id);
    }

    public function test_sensitive_fields_are_not_stored(): void
    {
        $log = app(AuditLogger::class)->record(
            'x.test',
            null,
            ['password' => 'a', 'phone' => '1', 'ship_street' => 's', 'name' => 'n'],
            ['remember_token' => 't', 'address' => 'addr', 'name' => 'm'],
        );

        $this->assertSame(['name' => 'n'], $log->old_values);
        $this->assertSame(['name' => 'm'], $log->new_values);
    }

    public function test_order_product_and_post_decisions_are_audited(): void
    {
        $moderator = User::factory()->moderator()->create();
        $partner = User::factory()->partner()->create();

        $order = Order::create(['user_id' => User::factory()->create()->id, 'customer_name' => 'A', 'status' => OrderStatus::Pending, 'subtotal' => '1', 'total_price' => '1']);
        $order->update(['status' => OrderStatus::Shipped]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.status_changed', 'subject_id' => $order->id]);

        $approved = $this->pendingProduct($partner);
        app(ProductService::class)->approve($approved, $moderator);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.approved', 'subject_id' => $approved->id]);

        $rejected = $this->pendingProduct($partner);
        app(ProductService::class)->reject($rejected, $moderator, 'Photo is blurry.');
        $this->assertSame('Photo is blurry.', AuditLog::query()->where('action', 'product.rejected')->firstOrFail()->new_values['reason']);

        $post = Post::factory()->pending()->create(['user_id' => $partner->id]);
        app(PostService::class)->approve($post, $moderator);
        $this->assertDatabaseHas('audit_logs', ['action' => 'post.approved', 'subject_id' => $post->id]);

        $other = Post::factory()->pending()->create(['user_id' => $partner->id]);
        app(PostService::class)->reject($other, $moderator, 'Too short.');
        $this->assertDatabaseHas('audit_logs', ['action' => 'post.rejected', 'subject_id' => $other->id]);
    }

    public function test_only_admin_opens_the_activity_log(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/activity-log')->assertOk();

        foreach ([User::factory()->moderator()->create(), User::factory()->operator()->create()] as $user) {
            $this->actingAs($user)->get('/admin/activity-log')->assertForbidden();
        }
    }

    public function test_audit_entries_cannot_be_edited_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $log = app(AuditLogger::class)->record('x.test', $admin);

        $this->assertTrue($admin->can('viewAny', AuditLog::class));
        $this->assertFalse($admin->can('update', $log));
        $this->assertFalse($admin->can('delete', $log));

        try {
            $log->update(['action' => 'changed']);
            $this->fail('update must throw');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_moderators_are_notified_about_submissions_and_the_partner_about_decisions(): void
    {
        Notification::fake();

        $moderator = User::factory()->moderator()->create();
        $customer = User::factory()->create();
        $partner = User::factory()->partner()->create();

        $product = $this->pendingProduct($partner);

        Notification::assertSentTo($moderator, ModerationSubmitted::class);
        Notification::assertNotSentTo($customer, ModerationSubmitted::class);

        app(ProductService::class)->reject($product, $moderator, 'Photo is blurry.');

        Notification::assertSentTo($partner, ModerationDecision::class, fn (ModerationDecision $n) => ! $n->approved
            && str_contains($n->body, 'blurry')
            && in_array('mail', $n->via($partner), true));
    }

    public function test_decision_mail_contains_the_reason(): void
    {
        $partner = User::factory()->partner()->create();
        $mail = (new ModerationDecision('product', 'Honey', false, 'Photo is blurry.'))->toMail($partner);

        $this->assertStringContainsString('Photo is blurry.', (string) $mail->render());
    }

    public function test_notification_failure_does_not_block_approval(): void
    {
        $moderator = User::factory()->moderator()->create();
        $product = $this->pendingProduct(User::factory()->partner()->create());

        Notification::shouldReceive('send')->andThrow(new \RuntimeException('queue is down'));

        app(ProductService::class)->approve($product, $moderator);

        $this->assertSame(ApprovalStatus::Approved, $product->fresh()->approval_status);
    }
}
