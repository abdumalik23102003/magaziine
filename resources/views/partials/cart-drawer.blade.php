{{-- Shopping-cart slide-in drawer. Included once in layouts/app.blade.php (opened by the bag button in the header).
     Later: replace $cartItems with the real cart (session / database) via a controller or View composer. --}}
@php
    // 'img' = file name inside public/foodmart/images (your original template images)
    $cartItems = [
        ['id' => 'prod-1', 'img' => 'thumb-avocado.png',   'glow' => '0,245,155',  'name' => 'Hass Creamy Avocados (Pack of 4)',   'price' => '4.49', 'unit' => 'pack',  'qty' => 1, 'badge' => 'In Stock',         'badgeTone' => 'primary', 'pulse' => true,  'note' => 'Hand Selected • 1 pack'],
        ['id' => 'prod-2', 'img' => 'thumb-raspberries.png','glow' => '244,63,94', 'name' => 'Sweet Alpine Raspberries (6 oz)',    'price' => '3.79', 'unit' => 'punnet','qty' => 1, 'badge' => '-15% Deal',        'badgeTone' => 'lime',    'pulse' => false, 'note' => 'Peak Brix'],
        ['id' => 'prod-3', 'img' => 'thumb-cucumber.png',  'glow' => '0,245,155',  'name' => 'Crisp Farm Cucumbers',               'price' => '2.29', 'unit' => 'each',  'qty' => 1, 'badge' => 'Direct from Farm', 'badgeTone' => 'primary', 'pulse' => false, 'note' => 'Organic'],
        ['id' => 'prod-4', 'img' => 'thumb-tomatoes.png',  'glow' => '249,115,22', 'name' => 'Vine-Ripened Cherry Tomatoes',      'price' => '5.19', 'unit' => 'lb',    'qty' => 1, 'badge' => '-30% Deal',        'badgeTone' => 'lime',    'pulse' => false, 'note' => 'High Lycopene'],
        ['id' => 'prod-5', 'img' => 'thumb-bananas.png',   'glow' => '245,158,11', 'name' => 'Organic Fairtrade Bananas',          'price' => '1.99', 'unit' => 'bunch', 'qty' => 1, 'badge' => 'Fresh Daily',      'badgeTone' => 'primary', 'pulse' => false, 'note' => 'Bunch of 6'],
    ];
    $cartCount    = collect($cartItems)->sum('qty');
    $cartSubtotal = number_format(collect($cartItems)->sum(fn ($i) => (float) $i['price'] * $i['qty']), 2);
    $cartTax      = $cartCount > 0 ? '1.25' : '0.00';
    $cartTotal    = number_format((float) $cartSubtotal + (float) $cartTax, 2);
    $cartProgress = min(100, round((float) $cartSubtotal / 35 * 100));
@endphp

<!-- ================= SHOPPING CART SIDEBAR DRAWER (ACTIVE) ================= -->
<!-- Modal Backdrop -->
<div class="fixed inset-0 bg-black/75 backdrop-blur-md z-50 transition-opacity duration-300 opacity-0 pointer-events-none" id="cart-backdrop"></div>
<!-- Sliding Cart Drawer Container -->
<aside aria-label="Shopping Cart Drawer" class="fixed inset-y-0 right-0 w-full max-w-lg bg-[#0f1311] border-l border-white/10 shadow-[0_0_70px_rgba(0,0,0,0.9)] z-50 flex flex-col transition-transform duration-300 ease-out translate-x-full" id="cart-drawer">
<!-- Top Ambient Glow -->
<div class="absolute top-0 right-0 w-72 h-36 bg-primary/15 rounded-full blur-3xl pointer-events-none"></div>
<!-- Drawer Header -->
<div class="relative z-10 px-6 py-5 border-b border-white/10 bg-[#090b0a]/85 backdrop-blur-xl flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-xl bg-primary/20 border border-primary/40 flex items-center justify-center text-primary shadow-[0_0_18px_rgba(0,245,155,0.3)]">
<i class="bi bi-bag-check-fill text-lg"></i>
</div>
<div>
<div class="flex items-center gap-2.5">
<h3 class="font-display font-bold text-lg sm:text-xl text-text-crisp tracking-tight">Shopping Cart</h3>
<span class="px-2.5 py-0.5 rounded-full bg-primary text-black font-mono font-black text-[11px] tracking-wide shadow-[0_0_10px_rgba(0,245,155,0.35)]" id="cart-drawer-count">{{ $cartCount }} {{ $cartCount == 1 ? 'item' : 'items' }}</span>
</div>
<p class="text-[11px] text-text-muted font-mono uppercase tracking-wider">Fast Organic Express Dispatch</p>
</div>
</div>
<div class="flex items-center gap-2">
<button class="text-xs font-mono font-medium text-text-muted hover:text-red-400 px-2.5 py-1 rounded-md border border-white/5 hover:border-red-400/30 hover:bg-red-500/5 transition-all flex items-center gap-1.5" id="cart-clear-all-btn">
<i class="bi bi-trash3 text-xs"></i>
<span>Clear All</span>
</button>
<button aria-label="Close Shopping Cart" class="w-8 h-8 rounded-lg bg-surface-carbon border border-border-glass text-text-muted hover:text-text-crisp hover:border-primary/50 flex items-center justify-center transition-all hover:shadow-[0_0_12px_rgba(0,245,155,0.25)]" id="cart-close-btn">
<i class="bi bi-x-lg text-sm"></i>
</button>
</div>
</div>
<!-- Delivery Progress Banner (Unlocked Free Delivery) -->
<div class="relative z-10 bg-surface-obsidian/90 border-b border-white/5 px-6 py-3">
<div class="flex items-center justify-between text-xs mb-1.5">
<span class="text-text-crisp font-semibold flex items-center gap-1.5" id="cart-delivery-msg">
<i class="bi bi-lightning-charge-fill text-accent-lime"></i> <span id="cart-delivery-text">Free delivery unlocked!</span>
    </span>
<span class="text-primary font-mono font-bold text-[11px]">Orders over $35</span>
</div>
<div class="w-full h-1.5 rounded-full bg-white/10 overflow-hidden relative">
<div class="h-full bg-gradient-to-r from-accent-emerald via-primary to-accent-lime rounded-full shadow-[0_0_10px_rgba(0,245,155,0.8)] transition-all duration-300" id="cart-progress-bar" style="width: {{ $cartProgress }}%;"></div>
</div>
</div>
<!-- Drawer Body / Cart Items List -->
<div class="relative z-10 flex-1 overflow-y-auto custom-scrollbar p-6 space-y-3.5" id="cart-items-container">
@foreach ($cartItems as $item)
            <x-cart-item
                :id="$item['id']" :img="asset('foodmart/images/'.$item['img'])" :glow="$item['glow']" :name="$item['name']"
                :price="$item['price']" :unit="$item['unit']" :qty="$item['qty']"
                :badge="$item['badge']" :badge-tone="$item['badgeTone']" :pulse="$item['pulse']" :note="$item['note']"
            />
        @endforeach

        {{-- Blueprint JS clones when a product is added from a product card (FoodMartCart.add) --}}
        <template id="cart-item-template">
            <x-cart-item id="__ID__" emoji="__EMOJI__" img="__IMG__" glow="__GLOW__" name="__NAME__" price="__PRICE__" unit="each" note="__NOTE__" :pulse="true" />
        </template>

        <!-- Empty Cart State (hidden initially) -->
<div class="hidden flex-col items-center justify-center text-center py-20 px-4" id="cart-empty-state">
<div class="w-20 h-20 rounded-3xl bg-surface-carbon border border-white/10 flex items-center justify-center text-3xl text-zinc-600 mb-5 shadow-inner">
<i class="bi bi-basket3 text-zinc-500"></i>
</div>
<h4 class="font-display font-bold text-lg text-text-crisp mb-1.5">Your cart is empty</h4>
<p class="text-xs text-text-muted max-w-xs leading-relaxed mb-6">
      Looks like you haven't added any fresh seasonal harvest or artisan goods yet. Explore our departments to start filling it up!
    </p>
<button class="px-6 py-2.5 rounded-full border border-primary/40 hover:border-primary bg-primary/10 hover:bg-primary text-primary hover:text-black font-display text-xs font-bold uppercase tracking-wider transition-all" id="cart-shop-now-btn">
      Start Shopping Now
    </button>
</div>
</div>
<!-- Drawer Footer / Checkout Summary -->
<div class="relative z-10 p-6 border-t border-white/10 bg-[#090b0a]/95 backdrop-blur-xl space-y-4" id="cart-footer">
<!-- Breakdown Calculation -->
<div class="space-y-2 text-xs font-mono">
<div class="flex items-center justify-between text-zinc-400">
<span>Subtotal</span>
<span class="text-text-crisp font-semibold" id="cart-subtotal">${{ $cartSubtotal }}</span>
</div>
<div class="flex items-center justify-between text-zinc-400">
<span>Estimated Tax</span>
<span class="text-text-crisp font-semibold" id="cart-tax">${{ $cartTax }}</span>
</div>
<div class="flex items-center justify-between text-zinc-400">
<span class="flex items-center gap-1.5">Express Delivery <span class="text-[10px] text-accent-lime font-bold">(&gt; $35)</span></span>
<span class="text-primary font-bold">FREE ($0.00)</span>
</div>
<div class="pt-2 border-t border-white/10 flex items-center justify-between">
<span class="text-sm font-display font-bold text-text-crisp">Total Order Value:</span>
<span class="text-xl font-display font-black text-primary" id="cart-total-value">${{ $cartTotal }}</span>
</div>
</div>
<!-- Primary Checkout CTA Button -->
<button class="neon-glow-btn w-full py-4 rounded-full bg-primary hover:bg-emerald-400 text-surface-obsidian font-display font-bold text-xs sm:text-sm uppercase tracking-wider flex items-center justify-center gap-2.5 transition-all" id="cart-checkout-btn" data-checkout-url="{{ route('checkout') }}">
<i class="bi bi-shield-lock-fill text-base"></i>
<span id="cart-checkout-label">Proceed to Checkout (${{ $cartTotal }})</span>
</button>
<!-- Secondary Continue Shopping CTA Button -->
<button class="w-full py-2.5 rounded-full bg-transparent hover:bg-white/5 border border-white/10 hover:border-white/20 text-text-muted hover:text-text-crisp font-display font-medium text-xs uppercase tracking-wider transition-all" id="cart-continue-btn">
    Continue Shopping
  </button>
</div>
</aside>
