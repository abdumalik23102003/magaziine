<div class="lg:col-span-7 flex flex-col gap-space-md">
    <div class="relative w-full h-[460px] md:h-[540px] rounded-xl overflow-hidden bg-surface-slate flex items-center justify-center group shadow-xl">
        <div class="absolute inset-0 bg-gradient-to-t from-surface-obsidian via-transparent to-transparent z-10 opacity-70"></div>
        <div class="absolute -inset-10 bg-radial from-accent-hyper-emerald/15 to-transparent blur-3xl opacity-40 group-hover:opacity-60 transition-opacity"></div>
        <img alt="{{ $mainImage['alt'] }}" id="main-product-img" class="relative z-0 object-cover w-full h-full transform transition-transform duration-700 group-hover:scale-105" src="{{ $mainImage['src'] }}"/>
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
