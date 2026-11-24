{{-- Sevimlilar drawer'i. Qatorlar public/js/wishlist-drawer.js tomonidan serverdan kelgan holatdan chiziladi. --}}
<div class="fixed inset-0 bg-black/65 backdrop-blur-md z-50 transition-opacity duration-300 opacity-0 pointer-events-none" id="wishlist-backdrop"></div>
<aside aria-label="Liked Products Drawer" class="fixed inset-y-0 right-0 w-full max-w-md bg-[#0f1311] border-l border-white/10 shadow-[0_0_60px_rgba(0,0,0,0.85)] z-50 flex flex-col transition-transform duration-300 ease-out translate-x-full" id="wishlist-drawer">
    <div class="absolute top-0 right-0 w-64 h-32 bg-primary/10 rounded-full blur-2xl pointer-events-none"></div>

    <div class="relative z-10 px-6 py-5 border-b border-white/10 bg-[#090b0a]/75 backdrop-blur-xl flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary/15 border border-primary/30 flex items-center justify-center text-primary">
                <i class="bi bi-heart-fill text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-display font-bold text-lg text-text-crisp tracking-tight">Saved Favorites</h3>
                    <span class="px-2 py-0.5 rounded-full bg-accent-lime text-black font-mono font-black text-[10px] tracking-wide" id="drawer-counter-badge">{{ $wishlistData['count'] }} items</span>
                </div>
                <a href="{{ route('wishlist.show') }}" class="text-[11px] text-text-muted hover:text-primary font-mono uppercase tracking-wider transition-colors">Open full list</a>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="text-xs font-mono font-medium text-text-muted hover:text-accent-lime px-2.5 py-1 rounded-md border border-white/5 hover:border-accent-lime/30 transition-all" id="clear-wishlist-btn">Clear All</button>
            <button type="button" aria-label="Close Drawer" class="w-8 h-8 rounded-lg bg-surface-carbon border border-border-glass text-text-muted hover:text-text-crisp hover:border-primary/50 flex items-center justify-center transition-colors" id="close-wishlist-btn">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
    </div>

    <div class="relative z-10 flex-1 overflow-y-auto custom-scrollbar p-6">
        <div class="space-y-4" id="wishlist-items-list"></div>

        <div class="hidden flex-col items-center justify-center text-center py-16 px-4" id="wishlist-empty-state">
            <div class="w-16 h-16 rounded-2xl bg-surface-carbon border border-white/10 flex items-center justify-center text-2xl text-zinc-500 mb-4">
                <i class="bi bi-heartbreak"></i>
            </div>
            <h4 class="font-display font-bold text-base text-text-crisp mb-1">No saved items yet</h4>
            <p class="text-xs text-text-muted max-w-xs leading-relaxed mb-6">Tap the heart on any product to keep it here for later.</p>
            <a href="{{ route('catalog.index') }}" class="px-5 py-2 rounded-full border border-primary/40 hover:border-primary bg-primary/10 hover:bg-primary text-primary hover:text-black font-display text-xs font-bold uppercase tracking-wider transition-all" id="explore-items-btn">Explore Catalog</a>
        </div>
    </div>

    <div class="relative z-10 p-6 border-t border-white/10 bg-[#090b0a]/90 backdrop-blur-xl space-y-3" id="wishlist-footer">
        <div class="flex items-center justify-between text-xs font-mono">
            <span class="text-text-muted">Total Saved Value:</span>
            <span class="text-text-crisp font-bold font-display text-sm" id="wishlist-total-value">${{ $wishlistData['total'] }}</span>
        </div>
        <button type="button" class="neon-glow-btn w-full py-3.5 rounded-full bg-primary hover:bg-emerald-400 text-surface-obsidian font-display font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all" id="move-all-cart-btn">
            <i class="bi bi-bag-plus-fill text-base"></i> Move All to Cart
        </button>
        <button type="button" class="w-full py-2.5 rounded-full bg-transparent hover:bg-white/5 border border-white/10 hover:border-white/20 text-text-muted hover:text-text-crisp font-display font-medium text-xs uppercase tracking-wider transition-all" id="continue-shopping-btn">Continue Shopping</button>
    </div>
</aside>
