{{-- Savat drawer'i. Qatorlar public/js/cart-drawer.js tomonidan serverdan kelgan holatdan chiziladi. --}}
<div class="fixed inset-0 bg-black/75 backdrop-blur-md z-50 transition-opacity duration-300 opacity-0 pointer-events-none" id="cart-backdrop"></div>
<aside aria-label="Shopping Cart Drawer" class="fixed inset-y-0 right-0 w-full max-w-lg bg-[#0f1311] border-l border-white/10 shadow-[0_0_70px_rgba(0,0,0,0.9)] z-50 flex flex-col transition-transform duration-300 ease-out translate-x-full" id="cart-drawer">
    <div class="absolute top-0 right-0 w-72 h-36 bg-primary/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 px-6 py-5 border-b border-white/10 bg-[#090b0a]/85 backdrop-blur-xl flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/20 border border-primary/40 flex items-center justify-center text-primary shadow-[0_0_18px_rgba(0,245,155,0.3)]">
                <i class="bi bi-bag-check-fill text-lg"></i>
            </div>
            <div>
                <div class="flex items-center gap-2.5">
                    <h3 class="font-display font-bold text-lg sm:text-xl text-text-crisp tracking-tight">Shopping Cart</h3>
                    <span class="px-2.5 py-0.5 rounded-full bg-primary text-black font-mono font-black text-[11px] tracking-wide" id="cart-drawer-count">{{ $cartData['count'] }} items</span>
                </div>
                <a href="{{ route('cart.show') }}" class="text-[11px] text-text-muted hover:text-primary font-mono uppercase tracking-wider transition-colors">Open full cart page</a>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="text-xs font-mono font-medium text-text-muted hover:text-red-400 px-2.5 py-1 rounded-md border border-white/5 hover:border-red-400/30 hover:bg-red-500/5 transition-all flex items-center gap-1.5" id="cart-clear-all-btn">
                <i class="bi bi-trash3 text-xs"></i><span>Clear All</span>
            </button>
            <button type="button" aria-label="Close Shopping Cart" class="w-8 h-8 rounded-lg bg-surface-carbon border border-border-glass text-text-muted hover:text-text-crisp hover:border-primary/50 flex items-center justify-center transition-all" id="cart-close-btn">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
    </div>

    <div class="relative z-10 bg-surface-obsidian/90 border-b border-white/5 px-6 py-3">
        <div class="flex items-center justify-between text-xs mb-1.5">
            <span class="text-text-crisp font-semibold flex items-center gap-1.5">
                <i class="bi bi-lightning-charge-fill text-accent-lime"></i> <span id="cart-delivery-text">Free delivery from ${{ $cartData['free_from'] }}</span>
            </span>
            <span class="text-primary font-mono font-bold text-[11px]">Orders over ${{ $cartData['free_from'] }}</span>
        </div>
        <div class="w-full h-1.5 rounded-full bg-white/10 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-accent-emerald via-primary to-accent-lime rounded-full transition-all duration-300" id="cart-progress-bar" style="width: {{ $cartData['progress'] }}%;"></div>
        </div>
    </div>

    <div class="relative z-10 flex-1 overflow-y-auto custom-scrollbar p-6" id="cart-items-container">
        <div class="space-y-3.5" id="cart-items-list"></div>

        <div class="hidden flex-col items-center justify-center text-center py-20 px-4" id="cart-empty-state">
            <div class="w-20 h-20 rounded-3xl bg-surface-carbon border border-white/10 flex items-center justify-center text-3xl text-zinc-500 mb-5">
                <i class="bi bi-basket3"></i>
            </div>
            <h4 class="font-display font-bold text-lg text-text-crisp mb-1.5">Your cart is empty</h4>
            <p class="text-xs text-text-muted max-w-xs leading-relaxed mb-6">Add some fresh harvest or artisan goods and they will show up here.</p>
            <a href="{{ route('catalog.index') }}" class="px-6 py-2.5 rounded-full border border-primary/40 hover:border-primary bg-primary/10 hover:bg-primary text-primary hover:text-black font-display text-xs font-bold uppercase tracking-wider transition-all" id="cart-shop-now-btn">Start Shopping</a>
        </div>
    </div>

    <div class="relative z-10 p-6 border-t border-white/10 bg-[#090b0a]/95 backdrop-blur-xl space-y-4" id="cart-footer">
        <div class="space-y-2 text-xs font-mono">
            <div class="flex items-center justify-between text-zinc-400">
                <span>Subtotal</span>
                <span class="text-text-crisp font-semibold" id="cart-subtotal">${{ $cartData['subtotal'] }}</span>
            </div>
            <div class="flex items-center justify-between text-zinc-400 {{ $cartData['tax_rate'] ? '' : 'hidden' }}" id="cart-tax-row">
                <span>Estimated Tax</span>
                <span class="text-text-crisp font-semibold" id="cart-tax">${{ $cartData['tax'] }}</span>
            </div>
            <div class="flex items-center justify-between text-zinc-400">
                <span>Delivery</span>
                <span class="text-primary font-bold" id="cart-delivery-value">{{ $cartData['qualifies'] ? 'FREE' : 'at checkout' }}</span>
            </div>
            <div class="pt-2 border-t border-white/10 flex items-center justify-between">
                <span class="text-sm font-display font-bold text-text-crisp">Total:</span>
                <span class="text-xl font-display font-black text-primary" id="cart-total-value">${{ $cartData['total'] }}</span>
            </div>
        </div>
        <button type="button" class="neon-glow-btn w-full py-4 rounded-full bg-primary hover:bg-emerald-400 text-surface-obsidian font-display font-bold text-xs sm:text-sm uppercase tracking-wider flex items-center justify-center gap-2.5 transition-all" id="cart-checkout-btn">
            <i class="bi bi-shield-lock-fill text-base"></i>
            <span id="cart-checkout-label">Proceed to Checkout (${{ $cartData['total'] }})</span>
        </button>
        <button type="button" class="w-full py-2.5 rounded-full bg-transparent hover:bg-white/5 border border-white/10 hover:border-white/20 text-text-muted hover:text-text-crisp font-display font-medium text-xs uppercase tracking-wider transition-all" id="cart-continue-btn">Continue Shopping</button>
    </div>
</aside>
