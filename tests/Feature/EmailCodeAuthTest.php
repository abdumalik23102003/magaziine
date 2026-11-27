<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use App\Services\LoginCodeService;
use App\Support\OneTimeCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailCodeAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Kodni so'raydi va emailga yuborilgan ochiq kodni qaytaradi. */
    private function requestCode(string $email = 'new@example.test'): string
    {
        Mail::fake();

        $this->post('/login/code', ['email' => $email])->assertRedirect(route('login.code.verify'));

        $code = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code, $email): bool {
            $code = $mail->code;

            return $mail->hasTo(strtolower($email));
        });

        return (string) $code;
    }

    private function wrongCode(string $real): string
    {
        return $real === '000000' ? '111111' : '000000';
    }

    public function test_pages_load_for_guests(): void
    {
        $this->get('/login/code')->assertOk();
        $this->get('/login')->assertOk()->assertSee(route('login.code'), false);
    }

    public function test_verify_page_without_a_requested_email_redirects(): void
    {
        $this->get('/login/code/verify')->assertRedirect(route('login.code'));
    }

    public function test_signed_in_users_cannot_open_the_code_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/login/code')->assertRedirect();
    }

    public function test_only_a_hash_of_the_code_is_stored(): void
    {
        $code = $this->requestCode();

        $row = LoginCode::query()->firstOrFail();
        $this->assertNotSame($code, $row->code_hash);
        $this->assertSame(OneTimeCode::hash('new@example.test', $code, (string) config('app.key')), $row->code_hash);
        $this->assertTrue($row->expires_at->isFuture());
    }

    public function test_new_email_registers_a_verified_customer_and_signs_in(): void
    {
        $code = $this->requestCode('Fresh.User@Example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertRedirect('/');

        $user = User::query()->where('email', 'fresh.user@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->telegram_id);
        $this->assertSame('Fresh User', $user->name);
    }

    public function test_existing_verified_customer_signs_in(): void
    {
        $user = User::factory()->create(['email' => 'login@example.test']);
        $code = $this->requestCode('login@example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_code_with_spaces_is_accepted(): void
    {
        $code = $this->requestCode();

        $this->post('/login/code/verify', ['code' => substr($code, 0, 3).' '.substr($code, 3)])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_unverified_password_account_is_refused(): void
    {
        User::factory()->unverified()->create(['email' => 'squat@example.test']);
        $code = $this->requestCode('squat@example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('auth');
        $this->assertGuest();
    }

    public function test_staff_account_is_refused(): void
    {
        User::factory()->admin()->create(['email' => 'boss@example.test']);
        $code = $this->requestCode('boss@example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('auth');
        $this->assertGuest();
    }

    public function test_wrong_code_is_rejected_and_counted(): void
    {
        $code = $this->requestCode();

        $this->post('/login/code/verify', ['code' => $this->wrongCode($code)])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(1, (int) LoginCode::query()->firstOrFail()->attempts);
    }

    public function test_the_code_is_locked_after_too_many_wrong_attempts(): void
    {
        $code = $this->requestCode();

        for ($i = 0; $i < LoginCodeService::MAX_ATTEMPTS; $i++) {
            $this->post('/login/code/verify', ['code' => $this->wrongCode($code)]);
        }

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        $code = $this->requestCode();

        $this->travel(LoginCodeService::TTL_MINUTES + 1)->minutes();

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_code_works_only_once(): void
    {
        Mail::fake();
        $service = app(LoginCodeService::class);
        $service->issue('once@example.test');

        $code = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->assertTrue($service->verify('once@example.test', $code));
        $this->assertFalse($service->verify('once@example.test', $code));
    }

    public function test_requesting_again_too_fast_is_blocked(): void
    {
        $this->requestCode();

        $this->post('/login/code', ['email' => 'new@example.test'])->assertSessionHasErrors('email');

        Mail::assertSent(LoginCodeMail::class, 1);
    }

    public function test_a_new_code_replaces_the_previous_one(): void
    {
        Mail::fake();
        $service = app(LoginCodeService::class);

        $service->issue('swap@example.test');
        $first = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$first): bool {
            $first = $mail->code;

            return true;
        });

        $this->travel(LoginCodeService::RESEND_SECONDS + 1)->seconds();
        Mail::fake();
        $service->issue('swap@example.test');
        $second = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$second): bool {
            $second = $mail->code;

            return true;
        });

        if ($first !== $second) {
            $this->assertFalse($service->verify('swap@example.test', $first));
        }
        $this->assertTrue($service->verify('swap@example.test', $second));
    }

    public function test_hourly_limit_per_email(): void
    {
        Mail::fake();
        $service = app(LoginCodeService::class);

        for ($i = 0; $i < LoginCodeService::MAX_PER_HOUR; $i++) {
            $service->issue('busy@example.test');
            $this->travel(LoginCodeService::RESEND_SECONDS + 1)->seconds();
        }

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->issue('busy@example.test');
    }
}
