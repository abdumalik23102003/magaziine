@props(['story', 'name'])

<section class="brand-story" data-brand-story aria-label="{{ $story['title'] ?? $name.' story' }}">
    <div class="brand-story__stage">
        <video class="brand-story__video" data-src="{{ $story['video_url'] }}" @if ($story['poster_url']) poster="{{ $story['poster_url'] }}" @endif muted playsinline preload="none" disablepictureinpicture></video>
        <canvas class="brand-story__video brand-story__canvas" data-story-canvas aria-hidden="true"></canvas>
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
