<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_email_can_update_the_profile(): void
    {
        $user = User::factory()->create(['email' => null]);

        $this->actingAs($user)
            ->put('/profile', ['name' => 'Telegram Person', 'email' => '', 'phone' => '+998 90 111 22 33'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Telegram Person', $user->name);
        $this->assertNull($user->email);
    }

    public function test_a_user_with_an_email_cannot_clear_it(): void
    {
        $user = User::factory()->create(['email' => 'keep@example.test']);

        $this->actingAs($user)
            ->put('/profile', ['name' => $user->name, 'email' => ''])
            ->assertSessionHasErrors('email');
    }

    public function test_changing_the_email_resets_verification(): void
    {
        $user = User::factory()->create(['email' => 'old@example.test']);

        $this->actingAs($user)->put('/profile', ['name' => $user->name, 'email' => 'new@example.test'])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('new@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_keeping_the_same_email_keeps_verification(): void
    {
        $user = User::factory()->create(['email' => 'same@example.test']);

        $this->actingAs($user)->put('/profile', ['name' => 'Renamed', 'email' => 'same@example.test'])->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
