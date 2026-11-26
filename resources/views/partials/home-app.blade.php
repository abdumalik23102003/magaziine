{{-- Ilova bo'limi. URL lar .env dagi APP_STORE_URL / PLAY_STORE_URL --}}
@php
    $stores = [
        [
            'name' => 'App Store',
            'image' => 'app-store.jpg',
            'url' => config('pages.app_store_url'),
        ],
        [
            'name' => 'Google Play',
            'image' => 'google-play.jpg',
            'url' => config('pages.play_store_url'),
        ],
    ];
@endphp

<section class="dark-glass-card relative mt-10 overflow-hidden rounded-3xl p-5 sm:p-7 md:p-8">

    {{-- Background glow --}}
    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-emerald-500/10 blur-[100px]"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-green-500/10 blur-[100px]"></div>

    <div class="relative grid grid-cols-1 items-center gap-4 md:grid-cols-2 md:gap-6">

        {{-- LEFT: CONTENT --}}
        <div class="relative z-10 max-w-xl">

            <p class="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-primary">
                Foodmart App
            </p>

            <h2 class="mt-3 font-display text-2xl font-extrabold leading-tight text-text-crisp sm:text-3xl lg:text-4xl">
                Harvest in your pocket,
                <span class="bg-gradient-to-r from-emerald-400 via-green-400 to-lime-400 bg-clip-text text-transparent">
                    delivered in minutes.
                </span>
            </h2>

            <p class="mt-3 max-w-lg text-sm leading-relaxed text-text-muted sm:text-base">
                Track every dispatch live, reorder your weekly basket in one tap and unlock app-only deals.
            </p>

            {{-- Store badges --}}
            <div class="mt-5 flex flex-wrap items-center gap-4">
                @foreach ($stores as $store)
                    @php $tag = $store['url'] ? 'a' : 'span'; @endphp
                    <{{ $tag }}
                        @if ($store['url'])
                            href="{{ $store['url'] }}"
                            aria-label="{{ $store['name'] }}"
                        @else
                            title="Coming soon"
                        @endif
                        class="{{ $store['url'] ? '' : 'cursor-default opacity-60' }} group relative inline-flex rounded-xl border border-emerald-400/40 bg-emerald-400/5 p-1
                   shadow-[0_0_12px_rgba(16,185,129,0.15)]
                   transition-all duration-300
                   hover:-translate-y-1 hover:border-emerald-300
                   hover:shadow-[0_0_10px_rgba(16,185,129,0.5),0_0_25px_rgba(16,185,129,0.25)]"
                    >
                        <img
                            src="{{ asset('foodmart/images/' . $store['image']) }}"
                            alt="{{ $store['name'] }}"
                            class="h-12 w-auto rounded-lg object-contain"
                            loading="lazy"
                        >
                    </{{ $tag }}>
                @endforeach
            </div>
        </div>

        {{-- RIGHT: PHONE IMAGE --}}
        <div class="relative flex items-center justify-center md:justify-end">
            <div class="absolute h-56 w-56 rounded-full bg-emerald-500/10 blur-3xl sm:h-72 sm:w-72"></div>

            <img
                src="{{ asset('foodmart/images/phone.png') }}"
                alt="Foodmart mobile app"
                class="relative z-10 w-full max-w-[200px] object-contain drop-shadow-[0_20px_50px_rgba(0,0,0,0.35)] transition-transform duration-500 hover:-translate-y-2 sm:max-w-[240px] lg:max-w-[270px]"
                loading="lazy"
            >
        </div>

    </div>
</section>
