<div class="lg:col-span-7 flex flex-col gap-space-md">
    <div class="flex flex-wrap items-center justify-between gap-space-xs bg-surface-carbon p-space-sm rounded-xl">
        <div class="flex items-center gap-space-xs text-text-crisp font-label-md text-label-md">
            <span class="material-symbols-outlined text-accent-hyper-emerald text-[18px]">eco</span>
            <span class="text-accent-hyper-emerald font-semibold">Harvest Delta:</span>
            <span>{{ $product['harvest'] }}</span>
        </div>
        <div class="flex items-center gap-space-xs">
            <span class="font-label-sm text-label-sm uppercase text-text-muted">Microbiome Index:</span>
            <span class="px-space-xs py-0.5 rounded bg-surface-elevated text-accent-hyper-emerald font-headline-sm text-label-md font-bold">98/100</span>
        </div>
    </div>

    <div class="relative w-full h-[460px] md:h-[540px] rounded-xl overflow-hidden bg-surface-slate flex items-center justify-center group shadow-xl">
        <div class="absolute top-4 left-4 z-20 flex items-center gap-2 bg-surface-obsidian/80 backdrop-blur-md px-3 py-1.5 rounded-full shadow-md">
            <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-hyper-emerald opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-accent-hyper-emerald"></span></span>
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-text-crisp">3.4°C Cold-Chain Continuous</span>
        </div>
        <div class="absolute top-4 right-4 z-20 flex items-center gap-1.5 bg-surface-carbon/90 backdrop-blur-md px-3 py-1.5 rounded-full text-accent-electric-lime font-label-sm text-label-sm uppercase tracking-wider">
            <span class="material-symbols-outlined text-[14px]">verified</span> Demeter Biodynamic
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-surface-obsidian via-transparent to-transparent z-10 opacity-70"></div>
        <div class="absolute -inset-10 bg-radial from-accent-hyper-emerald/15 to-transparent blur-3xl opacity-40 group-hover:opacity-60 transition-opacity"></div>
        <img alt="{{ $mainImage['alt'] }}" id="main-product-img" class="relative z-0 object-cover w-full h-full transform transition-transform duration-700 group-hover:scale-105" src="{{ $mainImage['src'] }}"/>

        <div class="absolute bottom-4 left-4 right-4 z-20 flex flex-wrap items-center justify-between gap-space-xs bg-surface-obsidian/85 backdrop-blur-xl p-space-xs rounded-lg shadow-lg">
            <div class="flex items-center gap-1" id="view-mode-buttons">
                @foreach ([['cut', 'pie_chart', 'Cross-Section Anatomy'], ['360', '360', '360° Scan'], ['density', 'biotech', 'Lipid & Brix Scan']] as $i => [$mode, $icon, $label])
                    <button type="button" data-view="{{ $mode }}" class="view-btn px-3 py-1.5 rounded font-label-md text-label-md uppercase tracking-wider flex items-center gap-1.5 transition-all {{ $i === 0 ? 'active bg-surface-elevated text-accent-hyper-emerald' : 'bg-transparent text-text-muted hover:text-text-crisp' }}">
                        <span class="material-symbols-outlined text-[16px]">{{ $icon }}</span>{{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="hidden sm:flex items-center gap-2 pr-2 text-text-muted font-label-sm text-label-sm"><span class="material-symbols-outlined text-[16px] text-accent-hyper-emerald">verified_user</span> Cryo-Inspection Pass #A9</div>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-space-sm" id="thumb-strip">
        @foreach ($gallery as $i => $g)
            <button type="button" data-src="{{ $g['src'] }}" data-alt="{{ $g['alt'] }}" class="thumb-card group relative h-24 rounded-lg overflow-hidden bg-surface-slate p-0.5 text-left transition-all {{ $i === 0 ? 'ring-2 ring-accent-hyper-emerald shadow-md' : 'hover:ring-1 hover:ring-accent-hyper-emerald/50' }}">
                <img alt="{{ $g['alt'] }}" class="w-full h-full object-cover rounded" src="{{ $g['src'] }}"/>
                <span class="thumb-label absolute bottom-1 left-1.5 z-10 font-label-sm text-label-sm uppercase bg-surface-obsidian/90 px-1 py-0.5 rounded {{ $i === 0 ? 'text-accent-hyper-emerald' : 'text-text-muted group-hover:text-text-crisp' }}">{{ $g['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>
