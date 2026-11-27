<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TelegramLoginWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWidgetAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TEST-bot-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.bot_token' => self::TOKEN,
            'telegram.bot_username' => 'magaziine_bot',
            'telegram.widget_max_age' => 600,
        ]);
    }

    private function widgetData(int $id = 4242, ?int $authDate = null, string $token = self::TOKEN): array
    {
        $data = [
            'id' => (string) $id,
            'first_name' => 'Ali',
            'last_name' => 'Valiyev',
            'username' => 'ali_v',
            'auth_date' => (string) ($authDate ?? now()->getTimestamp()),
        ];
        $data['hash'] = TelegramLoginWidget::signature($data, $token);

        return $data;
    }

    private function hitCallback(array $query)
    {
        return $this->get('/auth/telegram/callback?'.http_build_query($query));
    }

    public function test_valid_widget_data_signs_in_and_creates_a_customer_without_email(): void
    {
        $this->hitCallback($this->widgetData())->assertRedirect('/');

        $user = User::query()->where('telegram_id', 4242)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email);
        $this->assertSame('ali_v', $user->telegram_username);
    }

    public function test_an_existing_telegram_user_is_reused(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['telegram_id' => 4242])->save();

        $this->hitCallback($this->widgetData())->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_tampered_data_is_rejected(): void
    {
        $query = $this->widgetData();
        $query['id'] = '1';

        $this->hitCallback($query)->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_data_signed_with_another_token_is_rejected(): void
    {
        $this->hitCallback($this->widgetData(token: '999:OTHER'))->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_stale_data_is_rejected(): void
    {
        $this->hitCallback($this->widgetData(authDate: now()->getTimestamp() - 3600))->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_staff_with_a_telegram_id_cannot_sign_in(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill(['telegram_id' => 4242])->save();

        $this->hitCallback($this->widgetData())->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_callback_is_not_found_when_widget_is_not_configured(): void
    {
        config(['telegram.bot_username' => null]);

        $this->hitCallback($this->widgetData())->assertNotFound();
    }

    public function test_login_page_shows_the_widget_only_when_configured(): void
    {
        $this->get('/login')->assertSee('telegram-widget.js', false)->assertSee('data-telegram-login="magaziine_bot"', false);

        config(['telegram.bot_username' => null]);
        $this->get('/login')->assertDontSee('telegram-widget.js', false);
    }
}
