{{-- Checkout xaritasi. $map: config('geocoding.map'), $pin: ['lat' => ?, 'lng' => ?, 'source' => 'old'|'saved'|null] --}}
<section id="map-block" class="dark-glass-card rounded-3xl p-6">
    <h2 class="font-display font-bold text-xl text-text-crisp mb-1">Delivery location</h2>
    <p class="mb-4 text-xs text-text-muted leading-relaxed">Drop a pin where we should deliver. The address below fills in for you and stays fully editable.</p>

    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="flex-1">
            <label for="map-search" class="sr-only">Search for an address</label>
            {{-- name atributi yo'q: bu maydon buyurtma bilan yuborilmaydi --}}
            <input id="map-search" type="search" maxlength="120" autocomplete="off" placeholder="Search for an address, then press Enter"
                   class="w-full bg-surface-carbon border border-border-glass hover:border-primary/40 focus:border-primary rounded-xl py-2.5 px-4 text-sm text-text-crisp placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
        <div class="flex gap-2">
            <button type="button" id="map-search-btn" class="rounded-xl border border-primary/50 bg-primary/10 px-4 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-primary transition-colors hover:bg-primary/20 disabled:opacity-50">
                <i class="bi bi-search"></i> Search
            </button>
            <button type="button" id="map-locate" class="hidden rounded-xl border border-border-glass bg-surface-carbon px-4 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-text-crisp transition-colors hover:border-primary/50">
                <i class="bi bi-crosshair"></i> My location
            </button>
        </div>
    </div>

    <ul id="map-results" class="mt-2 hidden divide-y divide-border-glass overflow-hidden rounded-xl border border-border-glass bg-surface-carbon" role="listbox" aria-label="Address search results"></ul>

    <div class="relative mt-4">
        <div id="delivery-map"
             class="h-72 w-full overflow-hidden rounded-2xl border border-border-glass sm:h-96"
             role="application" aria-label="Delivery location map"
             data-center-lat="{{ $map['center_lat'] }}" data-center-lng="{{ $map['center_lng'] }}" data-zoom="{{ $map['zoom'] }}"
             data-tiles="{{ $map['tiles'] }}" data-attribution="{{ $map['attribution'] }}"
             data-pin-lat="{{ $pin['lat'] }}" data-pin-lng="{{ $pin['lng'] }}" data-pin-source="{{ $pin['source'] }}"
             data-reverse-url="{{ route('checkout.geocode.reverse') }}" data-search-url="{{ route('checkout.geocode.search') }}"></div>
        <div id="map-loading" class="pointer-events-none absolute right-3 top-3 z-10 hidden items-center gap-2 rounded-full bg-black/70 px-3 py-1.5 text-[11px] font-mono text-text-crisp [&:not(.hidden)]:flex" role="status">
            <i class="bi bi-arrow-repeat animate-spin text-primary"></i> Loading…
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
        <p id="map-status" class="min-h-[1.25rem] text-xs font-mono text-text-muted" role="status" aria-live="polite"></p>
        <button type="button" id="map-apply" class="hidden rounded-full border border-primary/50 px-3 py-1 text-[11px] font-mono font-bold uppercase tracking-wider text-primary hover:bg-primary/10">Use map address</button>
    </div>

    <input type="hidden" name="delivery_lat" id="delivery_lat" value="{{ old('delivery_lat') }}">
    <input type="hidden" name="delivery_lng" id="delivery_lng" value="{{ old('delivery_lng') }}">
    <input type="hidden" name="country_code" id="country_code" value="{{ old('country_code') }}">
    @error('delivery_lat') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
    @error('delivery_lng') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
    @error('country_code') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
</section>
