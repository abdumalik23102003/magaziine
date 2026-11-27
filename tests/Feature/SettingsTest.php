<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_settings_with_all_themes_and_fonts(): void
    {
        $this->get('/settings')
            ->assertOk()
            ->assertSee('data-value="dark"', false)
            ->assertSee('data-value="mint"', false)
            ->assertSee('data-value="lavender"', false)
            ->assertSee('data-pref="font"', false)
            ->assertSee('data-pref="size"', false)
            ->assertSee('data-pref="motion"', false)
            ->assertSee('guest-avatar-input', false)
            ->assertDontSee('id="avatar-form"', false);
    }

    public function test_layout_loads_theme_assets_on_every_page(): void
    {
        $this->get('/settings')
            ->assertSee('js/prefs.js', false)
            ->assertSee('css/themes.css', false)
            ->assertSee('data-theme="dark"', false);
    }

    public function test_every_role_sees_the_upload_form(): void
    {
        foreach ([UserRole::Customer, UserRole::Partner, UserRole::Admin, UserRole::Moderator, UserRole::Operator] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/settings')
                ->assertOk()
                ->assertSee('id="avatar-form"', false)
                ->assertDontSee('guest-avatar-input', false);
        }
    }

    public function test_guest_cannot_upload_or_delete_a_server_avatar(): void
    {
        Storage::fake('public');

        $this->post('/settings/avatar', ['avatar' => UploadedFile::fake()->image('a.png')])->assertRedirect('/login');
        $this->delete('/settings/avatar')->assertRedirect('/login');
    }

    public function test_user_can_upload_and_replace_an_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/avatar', ['avatar' => UploadedFile::fake()->image('me.png', 600, 400)])
            ->assertRedirect('/settings')
            ->assertSessionHasNoErrors();

        $user->refresh();
        $first = $user->avatar_path;
        $this->assertNotNull($first);
        $this->assertStringStartsWith('avatars/'.$user->id.'-', $first);
        Storage::disk('public')->assertExists($first);
        $this->assertNotNull($user->avatarUrl());

        $this->actingAs($user)->post('/settings/avatar', ['avatar' => UploadedFile::fake()->image('new.jpg', 300, 300)]);

        $user->refresh();
        $this->assertNotSame($first, $user->avatar_path);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_avatar_must_be_a_small_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/settings/avatar', ['avatar' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])
            ->assertSessionHasErrors('avatar');
        $this->actingAs($user)->post('/settings/avatar', ['avatar' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('avatar');
        $this->actingAs($user)->post('/settings/avatar', ['avatar' => UploadedFile::fake()->image('huge.png')->size(5000)])
            ->assertSessionHasErrors('avatar');
        $this->actingAs($user)->post('/settings/avatar', [])->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_user_can_remove_the_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user)->post('/settings/avatar', ['avatar' => UploadedFile::fake()->image('me.png')]);
        $path = $user->fresh()->avatar_path;

        $this->actingAs($user)->delete('/settings/avatar')->assertRedirect('/settings');

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_partner_and_admin_can_have_avatars_and_filament_uses_them(): void
    {
        Storage::fake('public');

        foreach ([UserRole::Partner, UserRole::Admin] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertInstanceOf(HasAvatar::class, $user);
            $this->assertNull($user->getFilamentAvatarUrl());

            $this->actingAs($user)->post('/settings/avatar', ['avatar' => UploadedFile::fake()->image('p.png')])->assertSessionHasNoErrors();

            $this->assertSame($user->fresh()->avatarUrl(), $user->fresh()->getFilamentAvatarUrl());
            $this->assertNotNull($user->fresh()->getFilamentAvatarUrl());
        }
    }

    public function test_avatar_path_is_not_mass_assignable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_path' => 'avatars/hacked.png',
        ]);

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_avatar_is_shown_in_header_and_profile(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['name' => 'Zafar Karimov']);
        $this->actingAs($user)->post('/settings/avatar', ['avatar' => UploadedFile::fake()->image('me.png')]);
        $url = $user->fresh()->avatarUrl();

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee($url, false);
        $this->actingAs($user)->get('/')->assertOk()->assertSee($url, false);
    }

    public function test_guest_header_has_avatar_slot_and_settings_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-guest-avatar', false)
            ->assertSee(route('settings'), false);
    }

    public function test_filament_panels_link_to_site_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('/settings', false);
    }
}
