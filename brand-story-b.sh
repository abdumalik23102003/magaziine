set -euo pipefail

if [ ! -f app/Services/BrandStoryService.php ]; then
  echo "Avval brand-story-a.sh ni magaziine ildizida ishga tushiring." >&2
  exit 1
fi

if [ -f public/js/pages/brand-story.js ]; then
  echo "B qism allaqachon qo'llangan." >&2
  exit 1
fi

patch_file() {
  OLD="$2" NEW="$3" perl -0pi -e '$found ||= s/\Q$ENV{OLD}\E/$ENV{NEW}/; END { die "Anchor topilmadi\n" unless $found }' "$1" || {
    echo "Xato: $1 ichida anchor topilmadi: $2" >&2
    exit 1
  }
}

cat > resources/views/components/brand-story.blade.php <<'EOF'
@props(['story', 'name'])

<section class="brand-story" data-brand-story aria-label="{{ $story['title'] ?? $name.' story' }}">
    <div class="brand-story__stage">
        <video class="brand-story__video" data-src="{{ $story['video_url'] }}" @if ($story['poster_url']) poster="{{ $story['poster_url'] }}" @endif muted playsinline preload="none" disablepictureinpicture></video>
        <div class="brand-story__shade"></div>

        @if ($story['title'] || $story['subtitle'])
            <div class="brand-story__copy" data-story-copy>
                @if ($story['title'])
                    <h2 class="brand-story__title font-display">{{ $story['title'] }}</h2>
                @endif
                @if ($story['subtitle'])
                    <p class="brand-story__subtitle">{{ $story['subtitle'] }}</p>
                @endif
            </div>
        @endif

        <p class="brand-story__loading" data-story-loading>Loading story&hellip;</p>
        <p class="brand-story__hint" data-story-hint>Scroll <i class="bi bi-arrow-down"></i></p>
        <div class="brand-story__bar"><span data-story-bar></span></div>
    </div>
</section>
<noscript><style>.brand-story { display: none; }</style></noscript>
EOF

cat > public/css/brand-story.css <<'EOF'
.brand-story {
    --story-top: 81px;
    position: relative;
    width: 100%;
    height: 350vh;
    background: #000;
}

.brand-story__stage {
    position: sticky;
    top: var(--story-top);
    height: calc(100vh - var(--story-top));
    height: calc(100svh - var(--story-top));
    overflow: hidden;
}

.brand-story__video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.brand-story__shade {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(to top, rgba(0, 0, 0, .75), rgba(0, 0, 0, 0) 55%);
}

.brand-story__copy {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 14%;
    padding: 0 1.5rem;
    text-align: center;
    will-change: opacity, transform;
}

.brand-story__title {
    margin: 0 auto;
    max-width: 60rem;
    font-size: clamp(2rem, 6vw, 4.5rem);
    font-weight: 800;
    line-height: 1.05;
    color: #fff;
    overflow-wrap: anywhere;
}

.brand-story__subtitle {
    margin: 1rem auto 0;
    max-width: 40rem;
    font-size: clamp(1rem, 2vw, 1.35rem);
    color: rgba(255, 255, 255, .8);
    overflow-wrap: anywhere;
}

.brand-story__loading,
.brand-story__hint {
    position: absolute;
    left: 0;
    right: 0;
    margin: 0;
    text-align: center;
    font-size: .7rem;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .7);
}

.brand-story__loading { top: 1.5rem; }

.brand-story__hint { bottom: 1.25rem; }

.brand-story__bar {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: rgba(255, 255, 255, .15);
}

.brand-story__bar span {
    display: block;
    height: 100%;
    background: #34d399;
    transform: scaleX(0);
    transform-origin: left;
}

.brand-story.is-ready .brand-story__loading { display: none; }

.brand-story.is-static { height: auto; }

.brand-story.is-static .brand-story__stage { position: relative; top: 0; }

.brand-story.is-static .brand-story__shade,
.brand-story.is-static .brand-story__hint,
.brand-story.is-static .brand-story__bar,
.brand-story.is-static .brand-story__loading { display: none; }

.brand-story.is-static .brand-story__copy {
    top: 8%;
    bottom: auto;
    pointer-events: none;
    text-shadow: 0 2px 16px rgba(0, 0, 0, .8);
}
EOF

cat > public/js/pages/brand-story.js <<'EOF'
// Brand page: scroll position drives the story video (GSAP ScrollTrigger scrub).
(function () {
  const section = document.querySelector('[data-brand-story]');
  if (!section) return;

  const video = section.querySelector('video');
  const copy = section.querySelector('[data-story-copy]');
  const hint = section.querySelector('[data-story-hint]');
  const bar = section.querySelector('[data-story-bar]');
  const header = document.querySelector('header');
  const src = video.dataset.src;

  const headerHeight = () => (header ? header.offsetHeight : 0);
  const syncTop = () => section.style.setProperty('--story-top', headerHeight() + 'px');
  syncTop();

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const saveData = Boolean(navigator.connection && navigator.connection.saveData);

  function makeStatic() {
    section.classList.add('is-static', 'is-ready');
    video.controls = true;
    video.preload = 'metadata';
    if (!video.getAttribute('src')) video.src = src;
  }

  video.addEventListener('error', () => {
    if (video.getAttribute('src')) section.style.display = 'none';
    if (window.ScrollTrigger) window.ScrollTrigger.refresh();
  });

  if (reduced || saveData || !window.gsap || !window.ScrollTrigger) {
    makeStatic();
    return;
  }

  let progress = 0;
  let queued = false;

  function seek() {
    queued = false;
    if (video.readyState < 1 || !Number.isFinite(video.duration)) return;
    if (video.seeking) return;
    const target = progress * Math.max(video.duration - 0.05, 0);
    if (Math.abs(video.currentTime - target) > 0.02) video.currentTime = target;
  }

  function requestSeek() {
    if (queued) return;
    queued = true;
    window.requestAnimationFrame(seek);
  }

  function render(value) {
    progress = Math.min(Math.max(value, 0), 1);
    bar.style.transform = 'scaleX(' + progress + ')';
    hint.style.opacity = progress > 0.04 ? '0' : '1';
    if (copy) {
      const fade = Math.min(Math.max((progress - 0.6) / 0.25, 0), 1);
      copy.style.opacity = String(1 - fade);
      copy.style.transform = 'translateY(' + (-40 * fade) + 'px)';
    }
    requestSeek();
  }

  video.addEventListener('seeked', requestSeek);
  video.addEventListener('loadeddata', () => {
    section.classList.add('is-ready');
    requestSeek();
  });

  // iOS paints seeked frames only after the video has been played once.
  function unlock() {
    const attempt = video.play();
    if (attempt) attempt.then(() => { video.pause(); requestSeek(); }).catch(() => {});
  }
  video.addEventListener('loadedmetadata', () => { unlock(); requestSeek(); }, { once: true });
  window.addEventListener('touchstart', unlock, { once: true, passive: true });

  window.gsap.registerPlugin(window.ScrollTrigger);

  const state = { p: 0 };
  window.gsap.to(state, {
    p: 1,
    ease: 'none',
    onUpdate: () => render(state.p),
    scrollTrigger: {
      trigger: section,
      start: () => 'top top+=' + headerHeight(),
      end: 'bottom bottom',
      scrub: 0.5,
      invalidateOnRefresh: true,
    },
  });

  window.addEventListener('resize', syncTop);
  window.addEventListener('load', () => window.ScrollTrigger.refresh());

  function load(url) {
    video.preload = 'auto';
    video.src = url;
    video.load();
  }

  // A blob URL is fully seekable even when the server ignores HTTP Range requests (php artisan serve).
  fetch(src)
    .then((response) => {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.blob();
    })
    .then((blob) => load(URL.createObjectURL(blob)))
    .catch(() => load(src));
})();
EOF

patch_file resources/views/layouts/app.blade.php "        @include('partials.header')
" "        @include('partials.header')

        @yield('before_main')
"

SHOW=resources/views/pages/brands/show.blade.php

patch_file "$SHOW" "@section('content')
<x-breadcrumbs :items=\"\$breadcrumbs\" />
" "@if (\$story)
    @push('styles')
        <link rel=\"stylesheet\" href=\"{{ asset('css/brand-story.css') }}\">
    @endpush

    @section('before_main')
        <x-brand-story :story=\"\$story\" :name=\"\$brand->name\" />
    @endsection

    @push('scripts')
        <script src=\"https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js\"></script>
        <script src=\"https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js\"></script>
        <script src=\"{{ asset('js/pages/brand-story.js') }}\"></script>
    @endpush
@endif

@section('content')
<x-breadcrumbs :items=\"\$breadcrumbs\" />
"

CONTROLLER=app/Http/Controllers/BrandController.php

patch_file "$CONTROLLER" 'use App\Services\BrandService;
' 'use App\Services\BrandService;
use App\Services\BrandStoryService;
'

patch_file "$CONTROLLER" 'public function __construct(private BrandService $brands, private PostService $posts) {}' 'public function __construct(
        private BrandService $brands,
        private PostService $posts,
        private BrandStoryService $stories,
    ) {}'

patch_file "$CONTROLLER" "            'brand' => \$brand,
" "            'brand' => \$brand,
            'story' => \$request->hasAny(['page', 'sort']) ? null : \$this->stories->forPage(\$brand),
"

cat > tests/Feature/BrandStoryTest.php <<'EOF'
<?php

namespace Tests\Feature;

use App\Filament\Partner\Resources\Brands\Pages\EditBrandProfile;
use App\Models\Brand;
use App\Models\User;
use App\Services\BrandStoryService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandStoryTest extends TestCase
{
    use RefreshDatabase;

    private User $partner;

    private Brand $own;

    private Brand $other;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->own = Brand::factory()->create(['slug' => 'own-farm']);
        $this->other = Brand::factory()->create(['slug' => 'rival-farm']);

        $this->partner = User::factory()->partner()->create();
        $this->partner->brands()->attach($this->own);

        Filament::setCurrentPanel(Filament::getPanel('partner'));
    }

    private function withVideo(Brand $brand, bool $enabled = true, string $name = 'story.mp4'): string
    {
        $path = app(BrandStoryService::class)->directory($brand).'/'.$name;
        Storage::disk('public')->put($path, 'video');
        $brand->update(['story_video_path' => $path, 'story_enabled' => $enabled]);

        return $path;
    }

    public function test_page_without_video_has_no_story_section(): void
    {
        $this->get('/brands/own-farm')
            ->assertOk()
            ->assertDontSee('data-brand-story', false)
            ->assertDontSee('ScrollTrigger', false);
    }

    public function test_enabled_video_is_shown_with_title_and_subtitle(): void
    {
        $path = $this->withVideo($this->own);
        $this->own->update(['story_title' => 'From seed to shelf', 'story_subtitle' => 'Grown slowly']);

        $this->get('/brands/own-farm')
            ->assertOk()
            ->assertSee('data-brand-story', false)
            ->assertSee($path, false)
            ->assertSee('From seed to shelf')
            ->assertSee('Grown slowly')
            ->assertSee('ScrollTrigger.min.js', false);
    }

    public function test_story_is_skipped_while_sorting_or_paginating(): void
    {
        $this->withVideo($this->own);

        $this->get('/brands/own-farm?sort=newest')->assertOk()->assertDontSee('data-brand-story', false);
        $this->get('/brands/own-farm?page=2')->assertOk()->assertDontSee('data-brand-story', false);
    }

    public function test_disabled_or_missing_video_is_hidden(): void
    {
        $path = $this->withVideo($this->own, false);

        $this->get('/brands/own-farm')->assertOk()->assertDontSee('data-brand-story', false);

        $this->own->update(['story_enabled' => true]);
        Storage::disk('public')->delete($path);

        $this->get('/brands/own-farm')->assertOk()->assertDontSee('data-brand-story', false);
    }

    public function test_partner_uploads_a_video_from_the_brand_profile(): void
    {
        $this->actingAs($this->partner);

        Livewire::test(EditBrandProfile::class, ['record' => $this->own->getRouteKey()])
            ->fillForm([
                'story_video_path' => [UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4')],
                'story_enabled' => true,
                'story_title' => 'Our story',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $own = $this->own->fresh();

        $this->assertTrue($own->story_enabled);
        $this->assertSame('Our story', $own->story_title);
        $this->assertStringStartsWith("brands/videos/{$own->id}/", $own->story_video_path);
        Storage::disk('public')->assertExists($own->story_video_path);
    }

    public function test_wrong_file_type_is_rejected(): void
    {
        $this->actingAs($this->partner);

        Livewire::test(EditBrandProfile::class, ['record' => $this->own->getRouteKey()])
            ->fillForm(['story_video_path' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]])
            ->call('save')
            ->assertHasFormErrors(['story_video_path']);

        $this->assertNull($this->own->fresh()->story_video_path);
    }

    public function test_replacing_and_removing_a_video_deletes_the_old_file(): void
    {
        $first = $this->withVideo($this->own, true, 'first.mp4');
        $second = $this->withVideo($this->own, true, 'second.mp4');

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->own->update(['story_video_path' => null]);

        Storage::disk('public')->assertMissing($second);
    }

    public function test_deleting_a_brand_deletes_its_video(): void
    {
        $path = $this->withVideo($this->own);

        $this->own->delete();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_another_brands_video_path_is_rejected(): void
    {
        $theirs = $this->withVideo($this->other);

        $this->expectException(AuthorizationException::class);

        app(BrandStoryService::class)->assertOwnPath($this->own, $theirs);
    }

    public function test_foreign_files_are_never_deleted(): void
    {
        $theirs = $this->withVideo($this->other);

        app(BrandStoryService::class)->delete($this->own, $theirs);

        Storage::disk('public')->assertExists($theirs);
    }

    public function test_partner_cannot_update_another_brand(): void
    {
        $this->assertTrue($this->partner->can('update', $this->own));
        $this->assertFalse($this->partner->can('update', $this->other));

        $this->actingAs($this->partner);

        $this->assertContains($this->get('/partner/brand-profile/rival-farm/edit')->status(), [403, 404]);
    }
}
EOF

echo "B qism tayyor."
