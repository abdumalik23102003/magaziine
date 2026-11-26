<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use App\Models\User;
use App\Services\SupportChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['support.hours.always' => true]);
    }

    private function guest(string $token): static
    {
        return $this->withSession(['support_visitor' => $token]);
    }

    public function test_guest_starts_conversation(): void
    {
        $this->guest('tok-a')
            ->postJson('/support/chat/messages', ['body' => 'Salom', 'guest_name' => 'Ali'])
            ->assertCreated()
            ->assertJsonPath('messages.0.body', 'Salom')
            ->assertJsonPath('messages.0.sender_type', 'customer');

        $this->assertDatabaseHas('support_conversations', ['visitor_token' => 'tok-a', 'guest_name' => 'Ali', 'status' => 'open']);
        $this->assertDatabaseCount('support_messages', 1);
    }

    public function test_second_guest_cannot_see_conversation(): void
    {
        $this->guest('tok-a')->postJson('/support/chat/messages', ['body' => 'Maxfiy'])->assertCreated();

        $this->guest('tok-b')
            ->getJson('/support/chat')
            ->assertOk()
            ->assertJsonPath('conversation', null)
            ->assertJsonCount(0, 'messages');

        $this->guest('tok-b')->getJson('/support/chat/messages?after=0')->assertJsonCount(0, 'messages');
    }

    public function test_send_is_throttled(): void
    {
        $this->guest('tok-a');
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/support/chat/messages', ['body' => "m{$i}"])->assertCreated();
        }
        $this->postJson('/support/chat/messages', ['body' => 'ortiqcha'])->assertStatus(429);
    }

    public function test_after_parameter_returns_only_newer_messages(): void
    {
        $this->guest('tok-a');
        $first = $this->postJson('/support/chat/messages', ['body' => 'bir'])->json('messages.0.id');
        $this->postJson('/support/chat/messages', ['body' => 'ikki']);
        $this->postJson('/support/chat/messages', ['body' => 'uch']);

        $this->getJson('/support/chat/messages?after='.$first)
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.body', 'ikki');
    }

    public function test_guest_conversation_is_adopted_on_login(): void
    {
        $this->guest('tok-a')->postJson('/support/chat/messages', ['body' => 'Salom']);
        $user = User::factory()->create();

        app(SupportChatService::class)->adoptGuest($user, 'tok-a');

        $this->assertDatabaseHas('support_conversations', ['user_id' => $user->id, 'visitor_token' => null]);
        $this->assertSame(1, SupportConversation::where('user_id', $user->id)->count());
    }

    public function test_too_long_message_is_rejected(): void
    {
        $this->guest('tok-a')
            ->postJson('/support/chat/messages', ['body' => str_repeat('a', 1001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');

        $this->postJson('/support/chat/messages', ['body' => '   '])->assertStatus(422);
    }

    public function test_html_message_is_stored_raw_and_client_uses_text_content(): void
    {
        $html = '<script>alert(1)</script>';

        $this->guest('tok-a')
            ->postJson('/support/chat/messages', ['body' => $html])
            ->assertCreated()
            ->assertJsonPath('messages.0.body', $html);

        $js = file_get_contents(public_path('js/support-chat.js'));
        $this->assertStringContainsString('textContent', $js);
        $this->assertStringNotContainsString('innerHTML', $js);
    }

    public function test_outside_hours_adds_system_message_once(): void
    {
        config(['support.hours.always' => false, 'support.hours.start' => '00:00', 'support.hours.end' => '00:00']);
        $this->travelTo(now()->setTime(12, 0));

        $this->guest('tok-a');
        $this->postJson('/support/chat/messages', ['body' => 'bir'])->assertJsonCount(2, 'messages');
        $this->postJson('/support/chat/messages', ['body' => 'ikki'])->assertJsonCount(1, 'messages');
    }
}
