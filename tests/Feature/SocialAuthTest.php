<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.github.client_id' => 'gh-id',
            'services.github.client_secret' => 'gh-secret',
            'services.google.client_id' => 'g-id',
            'services.google.client_secret' => 'g-secret',
        ]);
    }

    /** @param  callable(): SocialiteUser  $resolve */
    private function fakeProvider(string $provider, callable $resolve): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('redirect')->andReturn(new RedirectResponse('https://provider.test/authorize'));
        $driver->shouldReceive('user')->andReturnUsing($resolve);

        $this->mock(Factory::class, function ($mock) use ($provider, $driver): void {
            $mock->shouldReceive('driver')->with($provider)->andReturn($driver);
        });
    }

    private function socialUser(string $id, ?string $email, array $raw = []): SocialiteUser
    {
        return (new SocialiteUser)
            ->setRaw($raw + ['email' => $email])
            ->map(['id' => $id, 'nickname' => 'nick', 'name' => 'Social Person', 'email' => $email, 'avatar' => null]);
    }

    public function test_redirect_goes_to_the_provider(): void
    {
        $this->fakeProvider('github', fn () => $this->socialUser('1', null));

        $this->get('/auth/github/redirect')->assertRedirect('https://provider.test/authorize');
    }

    public function test_provider_without_credentials_is_not_found(): void
    {
        config(['services.google.client_secret' => null]);

        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_unknown_provider_is_not_found(): void
    {
        $this->get('/auth/facebook/redirect')->assertNotFound();
    }

    public function test_login_page_lists_only_configured_providers(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/login')
            ->assertSee(route('auth.social.redirect', 'github'), false)
            ->assertDontSee(route('auth.social.redirect', 'google'), false);
    }

    public function test_new_visitor_gets_an_account_and_is_signed_in(): void
    {
        $this->fakeProvider('github', fn () => $this->socialUser('gh-1', 'Dev@Example.test'));

        $this->get('/auth/github/callback')->assertRedirect('/');

        $user = User::query()->where('email', 'dev@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'github', 'provider_user_id' => 'gh-1']);
    }

    public function test_a_returning_visitor_reuses_the_same_account(): void
    {
        $this->fakeProvider('github', fn () => $this->socialUser('gh-1', 'dev@example.test'));

        $this->get('/auth/github/callback');
        $this->post('/logout');
        $this->get('/auth/github/callback')->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, SocialAccount::query()->count());
    }

    public function test_it_links_to_an_existing_customer_with_a_verified_email(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.test']);
        $this->fakeProvider('github', fn () => $this->socialUser('gh-9', 'owner@example.test'));

        $this->get('/auth/github/callback')->assertRedirect('/');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::query()->count());
        $this->assertDatabaseHas('social_accounts', ['user_id' => $existing->id, 'provider_user_id' => 'gh-9']);
    }

    public function test_it_never_links_to_an_unverified_account(): void
    {
        User::factory()->unverified()->create(['email' => 'squat@example.test']);
        $this->fakeProvider('github', fn () => $this->socialUser('gh-2', 'squat@example.test'));

        $this->get('/auth/github/callback')->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
        $this->assertSame(0, SocialAccount::query()->count());
    }

    public function test_staff_accounts_are_never_linked(): void
    {
        User::factory()->admin()->create(['email' => 'boss@example.test']);
        $this->fakeProvider('github', fn () => $this->socialUser('gh-3', 'boss@example.test'));

        $this->get('/auth/github/callback')->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
        $this->assertSame(0, SocialAccount::query()->count());
    }

    public function test_google_email_that_is_not_verified_is_not_trusted(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $this->fakeProvider('google', fn () => $this->socialUser('g-1', 'owner@example.test', ['email_verified' => false]));

        $this->get('/auth/google/callback')->assertRedirect('/');

        $account = SocialAccount::query()->firstOrFail();
        $this->assertNotSame($owner->id, $account->user_id);
        $this->assertNull($account->user->email);
        $this->assertSame(2, User::query()->count());
    }

    public function test_google_verified_email_links_to_the_existing_account(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $this->fakeProvider('google', fn () => $this->socialUser('g-2', 'owner@example.test', ['email_verified' => true]));

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertAuthenticatedAs($owner);
    }

    public function test_cancelled_sign_in_returns_to_the_login_page(): void
    {
        $this->get('/auth/github/callback?error=access_denied')->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_provider_failures_do_not_leak_details(): void
    {
        $this->fakeProvider('github', fn () => throw new \RuntimeException('secret boom'));

        $this->get('/auth/github/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['auth' => 'Could not sign in with GitHub. Please try again.']);

        $this->assertGuest();
    }
}
