<!-- Announcement Banner -->
<div class="border-b border-border-glass bg-surface-obsidian/90 backdrop-blur text-xs py-2 px-4 text-text-muted">
<div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
<div class="flex items-center gap-2">
<span class="inline-flex items-center justify-center w-2 h-2 rounded-full bg-primary animate-pulse"></span>
<span class="text-text-crisp font-medium">Free express delivery on orders over $35</span>
<span class="text-zinc-600">|</span>
<span class="text-zinc-400">Promo:</span>
<span class="font-mono text-primary font-bold px-2 py-0.5 rounded bg-primary/10 border border-primary/20 tracking-wider">FRESHORGANIC</span>
</div>
<div class="hidden md:flex items-center gap-3 text-zinc-400">
<button type="button" id="location-btn" data-url="{{ route('location.resolve') }}" title="Update my location" aria-label="Update my location" class="water-ripple-target group flex items-center gap-1.5 rounded-full px-3 py-1 hover:bg-white/5 hover:text-text-crisp transition-colors cursor-pointer">
<i class="bi bi-geo-alt-fill text-primary"></i>
<span>Hub:</span>
<strong id="location-label" class="text-text-crisp max-w-[11rem] truncate">{{ session('location', config('location.default')) }}</strong>
<i id="location-refresh" class="bi bi-arrow-repeat text-zinc-500 group-hover:text-primary"></i>
</button>
<button type="button" id="hotline-btn" title="Open live support chat" aria-label="Open live support chat" class="water-ripple-target flex items-center gap-1.5 rounded-full px-3 py-1 hover:bg-white/5 hover:text-text-crisp transition-colors cursor-pointer">
<i class="bi bi-headset text-primary"></i> 24/7 Concierge Hotline
</button>
</div>
</div>
</div>
