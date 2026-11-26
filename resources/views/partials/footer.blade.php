<footer class="border-t border-border-glass bg-surface-obsidian/95 mt-24">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 pb-12 border-b border-border-glass">
<div class="lg:col-span-5">
<a class="flex items-center gap-3 mb-4 group" href="/">
<div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center text-surface-obsidian font-black text-lg shadow-[0_0_16px_rgba(0,245,155,0.4)] group-hover:scale-105 transition-transform">
<i class="bi bi-basket2-fill"></i>
</div>
<span class="font-display font-bold text-2xl text-text-crisp">
              FOOD<span class="text-primary italic">MART</span>
</span>
</a>
<p class="text-sm text-text-muted leading-relaxed max-w-sm mb-6">
            Your neighborhood organic grocer engineered for peak freshness. Delivering certified regenerative farm harvest, artisan sourdough, and high-nutrition pantry staples.
          </p>
<div class="flex items-center gap-3 text-sm text-text-muted">
<span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></span>
<span class="font-mono text-xs text-text-crisp">Cold-chain certified &amp; carbon-neutral logistics</span>
</div>
</div>
<div class="lg:col-span-2">
<h5 class="font-display font-bold text-sm uppercase tracking-wider text-text-crisp mb-4">Shop</h5>
<ul class="space-y-2.5 text-sm text-text-muted font-medium">
@foreach ($navCategories as $navCategory)
<li><a class="hover:text-primary transition-colors" href="{{ route('catalog.category', $navCategory->slug) }}">{{ $navCategory->name }}</a></li>
@endforeach
<li><a class="hover:text-primary transition-colors" href="{{ route('catalog.index', ['discounted' => 1]) }}">Special Deals %</a></li>
</ul>
</div>
<div class="lg:col-span-2">
<h5 class="font-display font-bold text-sm uppercase tracking-wider text-text-crisp mb-4">Support</h5>
<ul class="space-y-2.5 text-sm text-text-muted font-medium">
<li><a class="hover:text-primary transition-colors" href="{{ route('blog.index') }}">Journal</a></li>
<li><a class="hover:text-primary transition-colors" href="{{ route('profile') }}">Track Order</a></li>
<li><a class="hover:text-primary transition-colors" href="{{ route('pages.show', 'refund-policy') }}">Refund Policy</a></li>
<li><a class="hover:text-primary transition-colors" href="{{ route('pages.show', 'delivery-area') }}">Delivery Area</a></li>
<li><a class="hover:text-primary transition-colors" href="tel:+998901886641">Contact Support</a></li>
</ul>
</div>
<div class="lg:col-span-3">
<h5 class="font-display font-bold text-sm uppercase tracking-wider text-text-crisp mb-4">Need help?</h5>
<p class="text-xs text-text-muted mb-4 leading-relaxed">Questions about an order or delivery? Our team is one message away.</p>
<a class="block text-sm font-display font-semibold text-text-crisp hover:text-primary transition-colors mb-3" href="tel:+998901886641">+998-90-188-6641</a>
<button type="button" onclick="window.dispatchEvent(new CustomEvent('support-chat:toggle', { detail: { handled: false } }))" class="water-ripple-target px-6 py-2.5 rounded-xl bg-primary hover:bg-emerald-400 text-surface-obsidian font-display font-bold text-xs uppercase tracking-wider transition-colors cursor-pointer">Open live chat</button>
</div>
</div>
<div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs font-mono text-zinc-500">
<div>© {{ date('Y') }} FoodMart. All rights reserved.</div>
<div class="flex items-center gap-6">
<a class="hover:text-primary transition-colors" href="{{ route('pages.show', 'privacy-policy') }}">Privacy Policy</a>
<a class="hover:text-primary transition-colors" href="{{ route('pages.show', 'terms-of-service') }}">Terms of Service</a>
<a class="hover:text-primary transition-colors" href="{{ route('pages.show', 'security') }}">Security</a>
</div>
</div>
</div>
</footer>
