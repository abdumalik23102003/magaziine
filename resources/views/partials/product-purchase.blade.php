<div class="lg:col-span-5 flex flex-col gap-space-md" id="product-root" data-product-id="{{ $product->id }}" data-name="{{ $info['name'] }}" data-price="{{ $packs[0]['price'] }}">

    {{-- Title block --}}
    <div class="bg-surface-carbon p-space-lg rounded-xl shadow-lg flex flex-col gap-space-xs">
        <div class="flex items-center justify-between gap-space-xs">
            <span class="font-label-md text-label-md uppercase tracking-wider text-accent-electric-lime font-semibold">{{ $info['farm'] }}</span>
            <span id="discount-badge" class="px-2 py-0.5 rounded-full bg-accent-electric-lime/10 text-accent-electric-lime font-label-sm text-label-sm uppercase tracking-wider font-bold {{ $packs[0]['badge'] ? '' : 'hidden' }}">{{ $packs[0]['badge'] }}</span>
        </div>
        <h1 class="font-headline-lg text-headline-lg text-text-crisp font-semibold tracking-tight mt-1">{{ $info['name'] }}</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">{{ $info['tagline'] }}</p>
        <div class="flex items-baseline gap-space-sm pt-space-sm">
            <span class="font-display text-headline-lg text-accent-hyper-emerald font-bold tracking-tight" id="display-price">${{ number_format($packs[0]['price'], 2) }}</span>
            <span id="old-price" class="font-headline-sm text-headline-sm text-text-muted line-through {{ $info['was'] ? '' : 'hidden' }}">{{ $info['was'] ? '$'.number_format($info['was'], 2) : '' }}</span>
            <span class="font-label-md text-label-md text-text-muted uppercase tracking-wider ml-auto" id="unit-price">Per {{ $product->unit->name }}</span>
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
                    <x-pack-option :key="$p['key']" :label="$p['label']" :name="$p['name']" :price="$p['price']" :per="$p['per']" :note="$p['note']" :old="$p['old']" :badge="$p['badge']" :active="$i === 0"/>
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
                <span id="cta-text">+ Add to Cart • ${{ number_format($packs[0]['price'], 2) }}</span>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-space-sm pt-space-xs">
            <button type="button" id="wishlist-btn" class="flex items-center justify-center gap-2 py-space-sm px-space-md rounded-lg bg-surface-slate hover:bg-surface-elevated text-text-crisp font-label-md text-label-md uppercase tracking-wider transition-colors">
                <span class="material-symbols-outlined text-[18px]" id="fav-icon">favorite_border</span><span id="fav-text">Save to Liked</span>
            </button>
        </div>

    </div>
</div>
