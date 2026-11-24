<header class="sticky top-0 z-50 bg-surface-obsidian/85 backdrop-blur-xl border-b border-border-glass">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-6">
<!-- Brand Logo with Interactive 3D Perspective, Orbit Ring and Hover Scaling -->
<div class="brand-logo-container">
<a class="flex items-center gap-3.5 group brand-logo-link py-1 px-1.5 rounded-xl cursor-pointer" href="/">
<!-- Animated Orbiting Particle Ring & Halo Icon -->
<div class="relative flex items-center justify-center">
<div class="orbit-ring"></div>
<div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-accent-emerald via-primary to-accent-lime flex items-center justify-center text-surface-obsidian font-black text-xl logo-icon-halo relative z-10">
<i class="bi bi-basket2-fill"></i>
</div>
</div>
<!-- 3D Perspective Tilt Text Container -->
<div class="flex flex-col brand-3d-text-wrap select-none">
<span class="font-display font-black text-2xl sm:text-[26px] tracking-tight text-text-crisp flex items-center brand-3d-heading leading-none">
            FOOD<span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent-lime italic font-black pl-0.5 brand-tt-glow">MAR<span class="inline-block relative">T</span></span>
</span>
<span class="text-[9px] uppercase tracking-[0.28em] text-text-muted font-bold mt-1 group-hover:text-primary transition-colors">
            Performance Organic
          </span>
</div>
</a>
</div>
<!-- Search Bar -->
<form action="{{ route('catalog.index') }}" method="GET" role="search" class="hidden lg:flex flex-1 max-w-lg relative">
<i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-primary/70 text-sm"></i>
<input class="w-full bg-surface-carbon border border-border-glass hover:border-primary/40 focus:border-primary rounded-full py-2.5 pl-11 pr-5 text-sm text-text-crisp placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" name="q" value="{{ is_string(request()->query('q')) ? request()->query('q') : '' }}" maxlength="100" placeholder="Search organic harvest, crisp greens, artisan sourdough..." type="search"/>
<div class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-mono text-zinc-500 bg-surface-elevated px-2 py-0.5 rounded border border-border-glass">
          ⌘K
        </div>
</form>
<!-- Quick Nav Action Counters (with unclipped, visible absolute badges) -->
<div class="flex items-center gap-3 sm:gap-4">
<div class="hidden sm:flex flex-col text-right mr-1">
<span class="text-[10px] font-mono uppercase tracking-widest text-primary font-bold">Fast Support</span>
<span class="text-xs font-semibold text-text-crisp font-display">1-800-456-7890</span>
</div>
<!-- Wishlist Button with isolated badge outside overflow -->
<div class="relative overflow-visible">
<button aria-label="Open Saved Favorites" id="wishlist-toggle-btn" class="water-ripple-target w-11 h-11 rounded-full bg-surface-carbon border border-border-glass flex items-center justify-center text-text-crisp hover:text-primary hover:border-primary/40 transition-colors">
<i class="bi bi-heart text-base"></i>
</button>
<span id="nav-wishlist-count" class="bg-accent-lime text-black border border-surface-obsidian pointer-events-none" style="position: absolute; top: -4px; right: -4px; z-index: 20; min-width: 18px; height: 18px; padding: 0 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; border-radius: 9999px; overflow: visible; box-shadow: 0 2px 5px rgba(0,0,0,0.5);">3</span>
</div>
<!-- Profile Button -->
<a href="{{ route('profile') }}" aria-label="{{ auth()->check() ? 'My profile' : 'Sign in' }}" title="{{ auth()->check() ? 'My profile' : 'Sign in' }}" class="water-ripple-target w-11 h-11 rounded-full bg-surface-carbon border flex items-center justify-center hover:text-primary hover:border-primary/40 transition-colors {{ request()->routeIs('profile') ? 'border-primary/60 text-primary' : 'border-border-glass text-text-crisp' }}">
<i class="bi bi-person text-lg"></i>
</a>
<!-- Cart Button with isolated badge outside overflow -->
<div class="relative overflow-visible" id="cart-counter-btn">
<button aria-label="Open Cart" id="cart-toggle-btn" class="water-ripple-target w-11 h-11 rounded-full bg-surface-carbon border border-border-glass flex items-center justify-center text-text-crisp hover:text-primary hover:border-primary/40 transition-colors">
<i class="bi bi-bag text-base"></i>
</button>
<span class="bg-primary text-black border border-surface-obsidian pointer-events-none transition-transform duration-200" id="nav-cart-count" style="position: absolute; top: -4px; right: -4px; z-index: 20; min-width: 18px; height: 18px; padding: 0 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; border-radius: 9999px; overflow: visible; box-shadow: 0 2px 5px rgba(0,0,0,0.5);">5</span>
</div>
</div>
</div>
<!-- Category Nav Pills -->
<div class="border-t border-border-glass/60 bg-surface-obsidian/50 overflow-x-auto scrollbar-none py-2.5 px-4 sm:px-6 lg:px-8">
<div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
<nav class="flex items-center gap-2 whitespace-nowrap" aria-label="Departments">
@php
    $pillOn = 'water-ripple-target px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-primary text-black flex items-center gap-1.5 shadow-[0_0_16px_rgba(0,245,155,0.35)] cursor-pointer';
    $pillOff = 'water-ripple-target px-3.5 py-1.5 rounded-full text-xs font-medium text-text-muted hover:text-text-crisp bg-surface-carbon/80 border border-border-glass hover:border-primary/30 transition-all flex items-center gap-1.5 cursor-pointer';
    $pillDeal = 'water-ripple-target px-3.5 py-1.5 rounded-full text-xs font-semibold text-accent-lime hover:text-white bg-surface-carbon/80 border border-accent-lime/30 transition-all flex items-center gap-1.5 cursor-pointer';
    $activeCategory = request()->route('category');
    $allActive = request()->routeIs('catalog.index') && ! request()->boolean('discounted');
@endphp
<a class="{{ $allActive ? $pillOn : $pillOff }}" href="{{ route('catalog.index') }}">
<i class="bi bi-grid-fill {{ $allActive ? '' : 'text-primary' }}"></i> All Departments
</a>
@foreach ($navCategories as $navCategory)
@php($isOn = $activeCategory && ($activeCategory->id === $navCategory->id || $activeCategory->parent_id === $navCategory->id))
<a class="{{ $isOn ? $pillOn : $pillOff }}" href="{{ route('catalog.category', $navCategory->slug) }}">
<i class="bi bi-tag {{ $isOn ? '' : 'text-primary' }}"></i> {{ $navCategory->name }}
</a>
@endforeach
<a class="{{ $pillDeal }}" href="{{ route('catalog.index', ['discounted' => 1]) }}">
<i class="bi bi-stars"></i> Special Deals %
</a>
</nav>

<button type="button" id="cursor-toggle" aria-pressed="true" title="Turn the glowing cursor on or off" class="water-ripple-target hidden xl:flex items-center gap-2 rounded-full border border-primary/30 px-3 py-1.5 text-xs font-mono text-primary/80 whitespace-nowrap hover:border-primary/60 transition-colors cursor-pointer">
<span id="cursor-toggle-dot" class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
<span id="cursor-toggle-label">ION CURSOR ENGINE ONLINE</span>
</button>
</div>
</div>
</header>
