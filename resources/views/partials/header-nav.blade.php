@php
    $pill = 'water-ripple-target flex h-[30px] shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full text-xs uppercase tracking-wider cursor-pointer transition-all';
    $pillOn = $pill.' px-4 font-bold bg-primary text-black shadow-[0_0_12px_var(--primary-glow)]';
    $pillOff = $pill.' px-3.5 font-medium text-text-muted hover:text-text-crisp bg-surface-carbon/80 border border-border-glass hover:border-primary/30';
    $pillDeal = $pill.' px-3.5 font-semibold text-accent-lime hover:text-white bg-surface-carbon/80 border border-accent-lime/30';
    $activeCategory = request()->route('category');
    $allActive = request()->routeIs('catalog.index') && ! request()->boolean('discounted');
@endphp

<div class="relative z-40 border-t border-border-glass/60 bg-surface-obsidian/50 px-4 py-2.5 sm:px-6 lg:px-8">
    <div class="relative mx-auto flex max-w-7xl items-center gap-2">
        <a class="{{ $allActive ? $pillOn : $pillOff }}" href="{{ route('catalog.index') }}">
            <i class="bi bi-grid-fill {{ $allActive ? '' : 'text-primary' }}"></i>
            <span class="sr-only sm:not-sr-only">All Departments</span>
        </a>

        @include('partials.nav-dropdowns')

        <nav data-fit-pills aria-label="Departments" class="-my-2 flex h-[46px] min-w-0 flex-1 flex-wrap content-start gap-x-2 gap-y-6 overflow-hidden px-1 py-2">
            @foreach ($navCategories as $navCategory)
                @php($isOn = $activeCategory && ($activeCategory->id === $navCategory->id || $activeCategory->parent_id === $navCategory->id))
                <a class="{{ $isOn ? $pillOn.' order-first' : $pillOff }}" href="{{ route('catalog.category', $navCategory->slug) }}">
                    <i class="bi bi-tag {{ $isOn ? '' : 'text-primary' }}"></i> {{ $navCategory->name }}
                </a>
            @endforeach
        </nav>

        <a class="{{ $pillDeal }}" href="{{ route('catalog.index', ['discounted' => 1]) }}">
            <i class="bi bi-stars"></i>
            <span class="sr-only sm:not-sr-only">Special Deals %</span>
        </a>

        <button type="button" id="cursor-toggle" aria-pressed="true" title="Turn the glowing cursor on or off" class="water-ripple-target hidden shrink-0 xl:flex items-center gap-2 rounded-full border border-primary/30 px-3 py-1.5 text-xs font-mono text-primary/80 whitespace-nowrap hover:border-primary/60 transition-colors cursor-pointer">
            <span id="cursor-toggle-dot" class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
            <span id="cursor-toggle-label">ION CURSOR ENGINE ONLINE</span>
        </button>
    </div>
</div>
