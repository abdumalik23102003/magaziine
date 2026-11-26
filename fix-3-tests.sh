set -euo pipefail

fail() {
  echo "Xato: $1" >&2
  exit 1
}

VIEW=resources/views/partials/home-app.blade.php
MAP=tests/Feature/CheckoutMapTest.php
GEO=tests/Unit/GeocodedAddressTest.php
CLEAN=tests/Feature/PlaceholderCleanupTest.php

[ -f "$VIEW" ] && [ -f "$MAP" ] && [ -f "$GEO" ] && [ -f "$CLEAN" ] || fail "loyiha ildizida ishga tushiring"

if grep -qF 'test_store_badges_link_only_when_a_url_is_set' "$CLEAN"; then
  echo "Tuzatish allaqachon qo'llangan."
  exit 0
fi

export VIEW_OPEN_OLD="                    <a
                        href=\"{{ \$store['url'] ?: '#' }}\"
                        aria-label=\"{{ \$store['name'] }}\"
"
export VIEW_OPEN_NEW="                    @php \$tag = \$store['url'] ? 'a' : 'span'; @endphp
                    <{{ \$tag }}
                        @if (\$store['url'])
                            href=\"{{ \$store['url'] }}\"
                            aria-label=\"{{ \$store['name'] }}\"
                        @else
                            title=\"Coming soon\"
                        @endif
"
export VIEW_CLASS_OLD='class="group relative inline-flex rounded-xl'
export VIEW_CLASS_NEW="class=\"{{ \$store['url'] ? '' : 'cursor-default opacity-60' }} group relative inline-flex rounded-xl"
export VIEW_CLOSE_OLD="                    </a>
                @endforeach"
export VIEW_CLOSE_NEW="                    </{{ \$tag }}>
                @endforeach"

export GEO_OLD="\$this->assertSame(
            ['street' => 'Amir Temur',"
export GEO_NEW="\$this->assertEquals(
            ['street' => 'Amir Temur',"

export MAP_OLD="        Http::fake(['*' => Http::response(['error' => 'Unable to geocode'])]);
        \$this->actingAs(\$user)->postJson('/checkout/geocode/reverse', ['lat' => 10, 'lng' => 10])->assertNotFound();

        Http::fake(['*' => Http::response('boom', 500)]);
        \$this->actingAs(\$user)->postJson('/checkout/geocode/reverse', ['lat' => 20, 'lng' => 20])->assertStatus(503);

        Http::fake(['*' => Http::response(\$this->nominatimReverse())]);
"
export MAP_NEW="        // Http::fake qayta chaqirilsa birinchi javob ustun qoladi, shuning uchun javoblar ketma-ket beriladi.
        Http::fake(['*' => Http::sequence()
            ->push(['error' => 'Unable to geocode'])
            ->push('boom', 500)
            ->push(\$this->nominatimReverse())]);

        \$this->actingAs(\$user)->postJson('/checkout/geocode/reverse', ['lat' => 10, 'lng' => 10])->assertNotFound();
        \$this->actingAs(\$user)->postJson('/checkout/geocode/reverse', ['lat' => 20, 'lng' => 20])->assertStatus(503);
"

export CLEAN_OLD="    public function test_catalog_cards_do_not_show_fake_stars(): void"
export CLEAN_NEW="    public function test_store_badges_link_only_when_a_url_is_set(): void
    {
        config(['pages.app_store_url' => 'https://apps.example.test/foodmart', 'pages.play_store_url' => null]);

        \$this->get('/')
            ->assertOk()
            ->assertSee('href=\"https://apps.example.test/foodmart\"', false)
            ->assertSee('title=\"Coming soon\"', false)
            ->assertDontSee('href=\"#\"', false);
    }

    public function test_catalog_cards_do_not_show_fake_stars(): void"

has() {
  OLD="$2" perl -0 -ne '$found = 1 if index($_, $ENV{OLD}) >= 0; END { exit($found ? 0 : 1) }' "$1" || fail "$1 ichida anchor topilmadi"
}

swap() {
  OLD="$2" NEW="$3" perl -0pi -e '$found ||= s/\Q$ENV{OLD}\E/$ENV{NEW}/; END { die "Anchor topilmadi\n" unless $found }' "$1" || fail "$1 ichida anchor topilmadi"
}

has "$VIEW" "$VIEW_OPEN_OLD"
has "$VIEW" "$VIEW_CLASS_OLD"
has "$VIEW" "$VIEW_CLOSE_OLD"
has "$GEO" "$GEO_OLD"
has "$MAP" "$MAP_OLD"
has "$CLEAN" "$CLEAN_OLD"

swap "$VIEW" "$VIEW_OPEN_OLD" "$VIEW_OPEN_NEW"
swap "$VIEW" "$VIEW_CLASS_OLD" "$VIEW_CLASS_NEW"
swap "$VIEW" "$VIEW_CLOSE_OLD" "$VIEW_CLOSE_NEW"
swap "$GEO" "$GEO_OLD" "$GEO_NEW"
swap "$MAP" "$MAP_OLD" "$MAP_NEW"
swap "$CLEAN" "$CLEAN_OLD" "$CLEAN_NEW"

echo "3 ta test uchun tuzatish qo'llandi."
