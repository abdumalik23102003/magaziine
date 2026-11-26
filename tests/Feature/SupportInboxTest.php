<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\SenderType;
use App\Models\SupportConversation;
use App\Models\User;
use App\Services\SupportChatService;
use App\Services\SupportInboxService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportInboxTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user->refresh();
    }

    private function conversation(?User $operator = null): SupportConversation
    {
        return SupportConversation::create([
            'visitor_token' => 'tok-'.uniqid(),
            'status' => $operator ? ConversationStatus::Assigned : ConversationStatus::Open,
            'operator_id' => $operator?->id,
            'last_message_at' => now(),
        ]);
    }

    public function test_operator_can_claim_and_reply(): void
    {
        $op = $this->staff('operator');
        $conv = $this->conversation();
        $service = app(SupportInboxService::class);

        $service->reply($conv, $op, 'Salom, yordam beraman');

        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'operator_id' => $op->id, 'status' => 'assigned']);
        $this->assertDatabaseHas('support_messages', ['conversation_id' => $conv->id, 'sender_type' => 'operator', 'sender_id' => $op->id]);
    }

    public function test_operator_cannot_reply_to_conversation_assigned_to_another(): void
    {
        $op1 = $this->staff('operator');
        $op2 = $this->staff('operator');
        $conv = $this->conversation($op1);

        $this->expectException(AuthorizationException::class);
        app(SupportInboxService::class)->reply($conv, $op2, 'Men yozaman');
    }

    public function test_operator_cannot_claim_assigned_but_admin_can_transfer(): void
    {
        $op1 = $this->staff('operator');
        $op2 = $this->staff('operator');
        $admin = $this->staff('admin');
        $conv = $this->conversation($op1);
        $service = app(SupportInboxService::class);

        try {
            $service->claim($conv, $op2);
            $this->fail('Claim ruxsat etilmasligi kerak edi');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $service->transfer($conv, $admin, $op2);
        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'operator_id' => $op2->id]);
    }

    public function test_customer_cannot_use_inbox(): void
    {
        $customer = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        app(SupportInboxService::class)->list($customer, 'open');
    }

    public function test_unread_count_and_mark_read(): void
    {
        $op = $this->staff('operator');
        $conv = $this->conversation();
        app(SupportChatService::class)->send($conv, 'Salom', SenderType::Customer);
        $service = app(SupportInboxService::class);

        $this->assertSame(1, $service->unreadConversationCount($op));

        $service->markRead($conv, $op);
        $this->assertSame(0, $service->unreadConversationCount($op));
    }

    public function test_close_and_reopen(): void
    {
        $op = $this->staff('operator');
        $conv = $this->conversation($op);
        $service = app(SupportInboxService::class);

        $service->close($conv, $op);
        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'status' => 'closed']);

        $service->reopen($conv, $op);
        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'status' => 'assigned', 'closed_at' => null]);
    }
}
