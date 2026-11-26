<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TEST-bot-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['telegram.bot_token' => self::TOKEN, 'telegram.init_data_max_age' => 86400]);
    }

    private function initData(int $id = 777, array $user = [], ?int $authDate = null, string $token = self::TOKEN): string
    {
        $fields = [
            'auth_date' => (string) ($authDate ?? now()->getTimestamp()),
            'query_id' => 'AAH-test',
            'signature' => 'third-party-signature',
            'user' => json_encode(['id' => $id] + $user + ['first_name' => 'Ali', 'last_name' => 'Valiyev', 'username' => 'ali_v']),
        ];
        ksort($fields);

        $check = collect($fields)->map(fn (string $value, string $key): string => "{$key}={$value}")->implode("\n");
        $fields['hash'] = hash_hmac('sha256', $check, hash_hmac('sha256', $token, 'WebAppData', true));

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    public function test_login_page_loads_the_telegram_script(): void
    {
        $this->get('/tma/login')
            ->assertOk()
            ->assertSee('telegram.org/js/telegram-web-app.js', false)
            ->assertSee('js/pages/tma-login.js', false)
            ->assertSee('name="csrf-token"', false);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/tma')->assertRedirect(route('tma.login'));
    }

    public function test_website_users_without_telegram_are_sent_to_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/tma')->assertRedirect(route('tma.login'));
    }

    public function test_valid_data_creates_a_customer_and_signs_in(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData()])
            ->assertOk()
            ->assertJson(['redirect' => route('tma.home')]);

        $user = User::query()->where('telegram_id', 777)->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ali Valiyev', $user->name);
        $this->assertSame('ali_v', $user->telegram_username);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertStringEndsWith('@telegram.invalid', $user->email);
        $this->get('/tma')->assertOk()->assertSee('Ali Valiyev');
    }

    public function test_second_sign_in_reuses_the_account(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData()])->assertOk();
        $this->postJson('/tma/auth', ['init_data' => $this->initData(user: ['username' => 'new_name'])])->assertOk();

        $this->assertSame(1, User::query()->count());
        $this->assertSame('new_name', User::query()->firstOrFail()->telegram_username);
    }

    public function test_the_requested_page_is_opened_after_sign_in(): void
    {
        $this->get('/tma?from=menu')->assertRedirect(route('tma.login'));

        $response = $this->postJson('/tma/auth', ['init_data' => $this->initData()])->assertOk();

        $this->assertStringContainsString('/tma?from=menu', $response->json('redirect'));
    }

    public function test_tampered_data_is_rejected(): void
    {
        $tampered = str_replace('777', '778', $this->initData());

        $this->postJson('/tma/auth', ['init_data' => $tampered])->assertStatus(422);

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_data_signed_with_another_bot_is_rejected(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData(token: '999:other')])->assertStatus(422);
        $this->postJson('/tma/auth', ['init_data' => 'user=%7B%22id%22%3A1%7D'])->assertStatus(422);
        $this->postJson('/tma/auth', [])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_old_and_future_data_is_rejected(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData(authDate: now()->subDays(2)->getTimestamp())])->assertStatus(422);
        $this->postJson('/tma/auth', ['init_data' => $this->initData(authDate: now()->addHour()->getTimestamp())])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_missing_bot_token_rejects_every_sign_in(): void
    {
        config(['telegram.bot_token' => null]);

        $this->postJson('/tma/auth', ['init_data' => $this->initData(token: '')])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_disabled_and_staff_accounts_cannot_use_telegram_sign_in(): void
    {
        User::factory()->create()->forceFill(['telegram_id' => 1, 'is_active' => false])->save();
        User::factory()->create()->forceFill(['telegram_id' => 2, 'role' => UserRole::Admin])->save();

        $this->postJson('/tma/auth', ['init_data' => $this->initData(1)])->assertStatus(422);
        $this->postJson('/tma/auth', ['init_data' => $this->initData(2)])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_switching_telegram_accounts_switches_the_user(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData(1)])->assertOk();
        $this->postJson('/tma/auth', ['init_data' => $this->initData(2)])->assertOk();

        $this->assertSame(2, (int) auth()->user()->telegram_id);
    }

    public function test_guest_cart_moves_to_the_telegram_account(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();

        $this->postJson('/tma/auth', ['init_data' => $this->initData()])->assertOk();

        $this->getJson('/cart/summary')->assertOk()->assertJsonPath('cart.count', 2);
        $this->assertDatabaseHas('carts', ['user_id' => auth()->id()]);
    }

    public function test_setup_command_connects_the_menu_button(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

        $this->artisan('telegram:setup', ['url' => 'https://shop.test/tma'])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/setChatMenuButton')
            && $request['menu_button']['web_app']['url'] === 'https://shop.test/tma');
    }

    public function test_setup_command_requires_https_and_reports_rejection(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401)]);

        $this->artisan('telegram:setup', ['url' => 'http://localhost/tma'])->assertFailed();
        Http::assertNothingSent();

        $this->artisan('telegram:setup', ['url' => 'https://shop.test/tma'])->assertFailed();
    }
}
