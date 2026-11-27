<header class="sticky top-0 z-50 bg-surface-obsidian/85 backdrop-blur-xl border-b border-border-glass">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-6">
<!-- Brand Logo with Interactive 3D Perspective, Orbit Ring and Hover Scaling -->
<div class="brand-logo-container">
<a class="flex items-center gap-3.5 group brand-logo-link py-1 px-1.5 rounded-xl cursor-pointer" href="{{ route('home') }}">
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
<span class="text-xs font-semibold text-text-crisp font-display">+998-90-188-6641</span>
</div>
<!-- Wishlist Button with isolated badge outside overflow -->
<div class="relative overflow-visible">
<button aria-label="Open Saved Favorites" id="wishlist-toggle-btn" class="water-ripple-target w-11 h-11 rounded-full bg-surface-carbon border border-border-glass flex items-center justify-center text-text-crisp hover:text-primary hover:border-primary/40 transition-colors">
<i class="bi bi-heart text-base"></i>
</button>
<span id="nav-wishlist-count" class="bg-accent-lime text-black border border-surface-obsidian pointer-events-none" style="position: absolute; top: -4px; right: -4px; z-index: 20; min-width: 18px; height: 18px; padding: 0 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; border-radius: 9999px; overflow: visible; box-shadow: 0 2px 5px rgba(0,0,0,0.5); display: {{ $wishlistData['count'] > 0 ? 'flex' : 'none' }};">{{ $wishlistData['count'] }}</span>
</div>
<!-- Settings Button (theme, font, profile photo) -->
<a href="{{ route('settings') }}" aria-label="Settings" title="Settings" class="water-ripple-target w-11 h-11 rounded-full bg-surface-carbon border flex items-center justify-center hover:text-primary hover:border-primary/40 transition-colors {{ request()->routeIs('settings') ? 'border-primary/60 text-primary' : 'border-border-glass text-text-crisp' }}">
<i class="bi bi-sliders text-base"></i>
</a>
<!-- Profile Button -->
<a href="{{ route('profile') }}" aria-label="{{ auth()->check() ? 'My profile' : 'Sign in' }}" title="{{ auth()->check() ? 'My profile' : 'Sign in' }}" class="water-ripple-target w-11 h-11 rounded-full bg-surface-carbon border flex items-center justify-center hover:text-primary hover:border-primary/40 transition-colors {{ request()->routeIs('profile') ? 'border-primary/60 text-primary' : 'border-border-glass text-text-crisp' }}">
<x-avatar :user="auth()->user()" size="w-full h-full" />
</a>
<!-- Cart Button with isolated badge outside overflow -->
<div class="relative overflow-visible" id="cart-counter-btn">
<button aria-label="Open Cart" id="cart-toggle-btn" class="water-ripple-target w-11 h-11 rounded-full bg-surface-carbon border border-border-glass flex items-center justify-center text-text-crisp hover:text-primary hover:border-primary/40 transition-colors">
<i class="bi bi-bag text-base"></i>
</button>
<span class="bg-primary text-black border border-surface-obsidian pointer-events-none transition-transform duration-200" id="nav-cart-count" style="position: absolute; top: -4px; right: -4px; z-index: 20; min-width: 18px; height: 18px; padding: 0 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; border-radius: 9999px; overflow: visible; box-shadow: 0 2px 5px rgba(0,0,0,0.5); display: {{ $cartData['count'] > 0 ? 'flex' : 'none' }};">{{ $cartData['count'] }}</span>
</div>
</div>
</div>
<!-- Category Nav Pills -->
@include('partials.header-nav')
</header>
