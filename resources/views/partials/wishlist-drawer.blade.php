{{-- Liked products drawer. Included once in layouts/app.blade.php (opened by the heart button in the header).
     Later: replace this array with the logged-in user's wishlist from the database. --}}
@php
    $wishlistItems = [
        ['id' => 'item-1', 'img' => 'thumb-avocado.png', 'glow' => '0,245,155',  'name' => 'Hass Creamy Avocados',     'note' => 'Pack of 4 • Hand Selected',   'price' => '4.49', 'oldPrice' => '5.99', 'tag' => 'Micro-Farm #12', 'tagClass' => 'text-zinc-500'],
        ['id' => 'item-2', 'img' => 'thumb-raspberries.png', 'glow' => '244,63,94',  'name' => 'Sweet Alpine Raspberries', 'note' => '1 lb punnet • Peak Sugar Brix', 'price' => '3.79', 'oldPrice' => '4.50', 'tag' => '-15% Deal',       'tagClass' => 'text-accent-lime'],
        ['id' => 'item-3', 'img' => 'thumb-tomatoes.png', 'glow' => '249,115,22', 'name' => 'Vine-Ripened Cherry Tomatoes', 'note' => '1 lb • High Lycopene',  'price' => '5.19', 'oldPrice' => '7.49', 'tag' => '-30% Deal',       'tagClass' => 'text-accent-lime'],
    ];
@endphp

<!-- ================= WISHLIST / LIKED PRODUCTS DRAWER ================= -->
<!-- Modal Backdrop -->
<div class="fixed inset-0 bg-black/65 backdrop-blur-md z-50 transition-opacity duration-300 opacity-0 pointer-events-none" id="wishlist-backdrop"></div>
<!-- Sliding Drawer Container -->
<aside aria-label="Liked Products Drawer" class="fixed inset-y-0 right-0 w-full max-w-md bg-[#0f1311] border-l border-white/10 shadow-[0_0_60px_rgba(0,0,0,0.85)] z-50 flex flex-col transition-transform duration-300 ease-out translate-x-full" id="wishlist-drawer">
<!-- Top Ambient Glow -->
<div class="absolute top-0 right-0 w-64 h-32 bg-primary/10 rounded-full blur-2xl pointer-events-none"></div>
<!-- Drawer Header -->
<div class="relative z-10 px-6 py-5 border-b border-white/10 bg-[#090b0a]/75 backdrop-blur-xl flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-9 h-9 rounded-xl bg-primary/15 border border-primary/30 flex items-center justify-center text-primary">
<i class="bi bi-heart-fill text-sm"></i>
</div>
<div>
<div class="flex items-center gap-2">
<h3 class="font-display font-bold text-lg text-text-crisp tracking-tight">Saved Favorites</h3>
<span class="px-2 py-0.5 rounded-full bg-accent-lime text-black font-mono font-black text-[10px] tracking-wide" id="drawer-counter-badge">{{ count($wishlistItems) }} items</span>
</div>
<p class="text-[11px] text-text-muted font-mono uppercase tracking-wider">Fast Harvest Wishlist</p>
</div>
</div>
<div class="flex items-center gap-2">
<button class="text-xs font-mono font-medium text-text-muted hover:text-accent-lime px-2.5 py-1 rounded-md border border-white/5 hover:border-accent-lime/30 hover:bg-accent-lime/5 transition-all" id="clear-wishlist-btn">
        Clear All
      </button>
<button aria-label="Close Drawer" class="w-8 h-8 rounded-lg bg-surface-carbon border border-border-glass text-text-muted hover:text-text-crisp hover:border-primary/50 flex items-center justify-center transition-colors" id="close-wishlist-btn">
<i class="bi bi-x-lg text-sm"></i>
</button>
</div>
</div>
<!-- Drawer Body / Liked Products List -->
<div class="relative z-10 flex-1 overflow-y-auto custom-scrollbar p-6 space-y-4" id="wishlist-items-container">
@foreach ($wishlistItems as $item)
    <x-wishlist-item
        :id="$item['id']" :img="asset('foodmart/images/'.$item['img'])" :glow="$item['glow']" :name="$item['name']"
        :note="$item['note']" :price="$item['price']" :old-price="$item['oldPrice']"
        :tag="$item['tag']" :tag-class="$item['tagClass']"
    />
@endforeach
<!-- Empty State (hidden by default) -->
<div class="hidden flex-col items-center justify-center text-center py-16 px-4" id="wishlist-empty-state">
<div class="w-16 h-16 rounded-2xl bg-surface-carbon border border-white/10 flex items-center justify-center text-2xl text-zinc-600 mb-4">
<i class="bi bi-heartbreak"></i>
</div>
<h4 class="font-display font-bold text-base text-text-crisp mb-1">No saved items yet</h4>
<p class="text-xs text-text-muted max-w-xs leading-relaxed mb-6">
        Explore our weekly farm-fresh harvest and click the heart icon on any product to save your essentials here.
      </p>
<button class="px-5 py-2 rounded-full border border-primary/40 hover:border-primary bg-primary/10 hover:bg-primary text-primary hover:text-black font-display text-xs font-bold uppercase tracking-wider transition-all" id="explore-items-btn">
        Explore Catalog
      </button>
</div>
</div>
<!-- Drawer Footer -->
<div class="relative z-10 p-6 border-t border-white/10 bg-[#090b0a]/90 backdrop-blur-xl space-y-3" id="wishlist-footer">
<!-- Subtotal note -->
<div class="flex items-center justify-between text-xs font-mono">
<span class="text-text-muted">Total Saved Value:</span>
<span class="text-text-crisp font-bold font-display text-sm" id="wishlist-total-value">${{ number_format(collect($wishlistItems)->sum(fn ($i) => (float) $i['price']), 2) }}</span>
</div>
<!-- Move All to Cart CTA -->
<button class="neon-glow-btn w-full py-3.5 rounded-full bg-primary hover:bg-emerald-400 text-surface-obsidian font-display font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all" id="move-all-cart-btn">
<i class="bi bi-bag-plus-fill text-base"></i> Move All to Cart
    </button>
<!-- Secondary Close / Continue CTA -->
<button class="w-full py-2.5 rounded-full bg-transparent hover:bg-white/5 border border-white/10 hover:border-white/20 text-text-muted hover:text-text-crisp font-display font-medium text-xs uppercase tracking-wider transition-all" id="continue-shopping-btn">
      Continue Shopping
    </button>
</div>
</aside>
