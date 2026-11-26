<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HeaderActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function fakeChicago(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => [
                    'city' => 'Chicago',
                    'state' => 'Illinois',
                    'country_code' => 'us',
                    'ISO3166-2-lvl4' => 'US-IL',
                ],
            ]),
        ]);
    }

    public function test_home_header_and_hero_use_real_links_and_buttons(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('id="location-btn"', false);
        $response->assertSee('Select location');
        $response->assertSee('id="hotline-btn"', false);
        $response->assertSee('id="cursor-toggle"', false);
        $response->assertSee('ION CURSOR ENGINE ONLINE');
        $response->assertSee('href="'.route('profile').'"', false);
        $response->assertSee('<a href="'.route('catalog.index').'" class="water-ripple-target neon-glow-btn', false);
        $response->assertSee('href="'.route('catalog.index', ['discounted' => 1]).'"', false);
    }

    public function test_location_is_resolved_cached_and_remembered_in_session(): void
    {
        $this->fakeChicago();

        $this->postJson('/location/resolve', ['lat' => 41.8781, 'lng' => -87.6298])
            ->assertOk()
            ->assertJson(['name' => 'Chicago, IL']);

        $this->get('/')->assertSee('Chicago, IL');

        $this->postJson('/location/resolve', ['lat' => 41.8790, 'lng' => -87.6301])->assertOk();

        Http::assertSentCount(1);
    }

    public function test_non_us_places_show_the_country_code(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => ['city' => 'Tashkent', 'country_code' => 'uz', 'ISO3166-2-lvl4' => 'UZ-TK'],
            ]),
        ]);

        $this->postJson('/location/resolve', ['lat' => 41.2995, 'lng' => 69.2401])
            ->assertOk()
            ->assertJson(['name' => 'Tashkent, UZ']);
    }

    public function test_location_validation(): void
    {
        Http::fake();

        $this->postJson('/location/resolve', ['lat' => 200, 'lng' => 10])->assertStatus(422);
        $this->postJson('/location/resolve', ['lat' => 'abc', 'lng' => 'x'])->assertStatus(422);
        $this->postJson('/location/resolve', [])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_geocoder_failure_returns_a_friendly_error(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response('oops', 500)]);

        $this->postJson('/location/resolve', ['lat' => 41.0, 'lng' => -87.0])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->get('/')->assertSee('Select location');
    }

    public function test_location_endpoint_is_throttled(): void
    {
        $this->fakeChicago();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/location/resolve', ['lat' => 41.88, 'lng' => -87.63])->assertOk();
        }

        $this->postJson('/location/resolve', ['lat' => 41.88, 'lng' => -87.63])->assertStatus(429);
    }

    public function test_guest_profile_button_leads_to_login_and_back(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }
}
