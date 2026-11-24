@props(['brand'])

<a href="{{ $brand['url'] }}" {{ $attributes->merge(['class' => 'water-ripple-target group dark-glass-card relative flex flex-col overflow-hidden rounded-3xl hover:border-primary/50 transition-all']) }}>
    <div class="relative h-32 shrink-0 overflow-hidden bg-gradient-to-br from-surface-slate to-surface-obsidian">
        @if ($brand['cover_url'])
            <img src="{{ $brand['cover_url'] }}" alt="" loading="lazy" draggable="false" class="h-full w-full object-cover opacity-70 transition-transform duration-500 group-hover:scale-105">
        @endif
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-surface-carbon to-transparent"></div>
    </div>

    <div class="relative -mt-8 flex flex-1 flex-col px-5 pb-5">
        <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border border-border-glass bg-surface-obsidian shadow-lg">
            @if ($brand['logo_url'])
                <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }} logo" loading="lazy" draggable="false" class="h-full w-full object-contain p-2">
            @else
                <span class="font-display text-2xl font-black text-primary">{{ mb_strtoupper(mb_substr($brand['name'], 0, 1)) }}</span>
            @endif
        </div>

        <h3 class="mt-3 font-display text-lg font-bold text-text-crisp transition-colors group-hover:text-primary">{{ $brand['name'] }}</h3>

        @if ($brand['tagline'])
            <p class="mt-1 line-clamp-2 text-sm text-text-muted">{{ $brand['tagline'] }}</p>
        @endif

        <div class="mt-auto flex items-center justify-between pt-4">
            <span class="text-[11px] font-mono text-text-muted">{{ $brand['products_count'] }} {{ $brand['products_count'] === 1 ? 'product' : 'products' }}</span>
            <span class="inline-flex items-center gap-1.5 text-xs font-display font-bold uppercase tracking-wider text-primary transition-all group-hover:gap-3">
                Shop now <i class="bi bi-arrow-right"></i>
            </span>
        </div>
    </div>
</a>
