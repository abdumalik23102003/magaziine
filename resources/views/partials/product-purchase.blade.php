<div class="lg:col-span-5 flex flex-col gap-space-md" id="product-root" data-name="{{ $product['name'] }}" data-price="{{ $packs[0]['price'] }}">

    {{-- Title block --}}
    <div class="bg-surface-carbon p-space-lg rounded-xl shadow-lg flex flex-col gap-space-xs">
        <div class="flex items-center justify-between gap-space-xs">
            <span class="font-label-md text-label-md uppercase tracking-wider text-accent-electric-lime font-semibold">{{ $product['farm'] }}</span>
            <span class="px-2 py-0.5 rounded-full bg-accent-electric-lime/10 text-accent-electric-lime font-label-sm text-label-sm uppercase tracking-wider font-bold">-25% Peak Deal</span>
        </div>
        <h1 class="font-headline-lg text-headline-lg text-text-crisp font-semibold tracking-tight mt-1">{{ $product['name'] }}</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">{{ $product['tagline'] }}</p>
        <div class="flex flex-wrap items-center gap-space-md pt-space-xs mt-space-xs">
            <div class="flex items-center gap-1.5">
                <x-star-rating :rating="$product['rating']"/>
                <span class="font-headline-sm text-label-lg text-text-crisp font-semibold">{{ $product['rating'] }}</span>
                <a class="font-body-sm text-body-sm text-text-muted underline hover:text-accent-hyper-emerald transition-colors" href="#reviews">({{ $product['reviews'] }} verified logs)</a>
            </div>
            <div class="flex items-center gap-1 px-2 py-0.5 rounded bg-surface-slate text-accent-hyper-emerald font-label-sm text-label-sm uppercase tracking-wider"><span class="material-symbols-outlined text-[14px]">water_drop</span> Peak Brix 14.2° (Grade A+)</div>
        </div>
        <div class="flex items-baseline gap-space-sm pt-space-sm">
            <span class="font-display text-headline-lg text-accent-hyper-emerald font-bold tracking-tight" id="display-price">${{ number_format($packs[0]['price'], 2) }}</span>
            <span class="font-headline-sm text-headline-sm text-text-muted line-through">${{ number_format($product['was'], 2) }}</span>
            <span class="font-label-md text-label-md text-text-muted uppercase tracking-wider ml-auto" id="unit-price">${{ number_format($packs[0]['per'], 2) }} / unit</span>
        </div>
    </div>

    {{-- Configuration --}}
    <div class="bg-surface-carbon p-space-lg rounded-xl shadow-lg flex flex-col gap-space-md">
        <div class="flex flex-col gap-space-xs">
            <div class="flex justify-between items-center">
                <label class="font-label-md text-label-md uppercase tracking-wider text-text-crisp font-semibold">Allocation Size</label>
                <span class="font-label-sm text-label-sm text-text-muted uppercase">Selected: <strong class="text-accent-hyper-emerald" id="size-label">{{ $packs[0]['name'] }}</strong></span>
            </div>
            <div class="grid grid-cols-3 gap-2" id="pack-options">
                @foreach ($packs as $i => $p)
                    <x-pack-option :key="$p['key']" :label="$p['label']" :name="$p['name']" :price="$p['price']" :per="$p['per']" :note="$p['note']" :active="$i === 0"/>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-space-xs">
            <div class="flex justify-between items-center">
                <label class="font-label-md text-label-md uppercase tracking-wider text-text-crisp font-semibold">Biological Ripeness Stage</label>
                <span class="font-label-sm text-label-sm text-accent-electric-lime uppercase">Tactile Ready</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2" id="ripeness-options">
                @foreach ($ripeness as $i => $r)
                    <x-ripeness-option :title="$r['title']" :sub="$r['sub']" :dot="$r['dot']" :active="$i === 1"/>
                @endforeach
            </div>
        </div>

        <div class="pt-space-xs flex flex-col sm:flex-row items-stretch gap-space-sm">
            <div class="flex items-center justify-between bg-surface-slate px-space-md py-space-sm rounded-lg w-full sm:w-36">
                <button type="button" id="qty-minus" class="text-text-muted hover:text-accent-hyper-emerald font-headline-sm text-headline-sm leading-none transition-colors px-1">−</button>
                <span class="font-headline-sm text-title text-text-crisp font-semibold" id="qty-val">1</span>
                <button type="button" id="qty-plus" class="text-text-muted hover:text-accent-hyper-emerald font-headline-sm text-headline-sm leading-none transition-colors px-1">+</button>
            </div>
            <button type="button" id="add-to-cart-btn" class="flex-1 py-space-md px-space-lg rounded-lg bg-accent-hyper-emerald text-surface-obsidian font-label-lg text-label-lg uppercase font-bold tracking-wider hover:scale-[1.01] active:scale-[0.99] transition-all shadow-[0_0_24px_rgba(0,245,155,0.4)] flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]">ac_unit</span>
                <span id="cta-text">+ Add to Cold-Chain Dispatch • ${{ number_format($packs[0]['price'], 2) }}</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm pt-space-xs">
            <button type="button" id="wishlist-btn" class="flex items-center justify-center gap-2 py-space-sm px-space-md rounded-lg bg-surface-slate hover:bg-surface-elevated text-text-crisp font-label-md text-label-md uppercase tracking-wider transition-colors">
                <span class="material-symbols-outlined text-[18px]" id="fav-icon">favorite_border</span><span id="fav-text">Save to Liked</span>
            </button>
            <button type="button" class="flex items-center justify-center gap-2 py-space-sm px-space-md rounded-lg bg-surface-slate hover:bg-surface-elevated text-accent-electric-lime font-label-md text-label-md uppercase tracking-wider transition-colors">
                <span class="material-symbols-outlined text-[18px]">autorenew</span><span>Weekly Sub (-10%)</span>
            </button>
        </div>

        <div class="p-space-md rounded-lg bg-surface-slate/80 flex items-start gap-space-sm">
            <div class="w-8 h-8 rounded-full bg-accent-electric-lime/10 flex items-center justify-center text-accent-electric-lime shrink-0 mt-0.5"><span class="material-symbols-outlined text-[18px] animate-pulse">electric_bolt</span></div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <span class="font-label-md text-label-md text-text-crisp uppercase font-semibold">Autonomous Cold Dispatch</span>
                    <span class="font-label-sm text-label-sm text-accent-electric-lime font-mono">18m 22s LEFT</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Order within 18 mins for Racing Precision Express. Arriving in ~35 mins via EV Climate Pod #18.</p>
            </div>
        </div>
    </div>
</div>
