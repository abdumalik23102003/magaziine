@props(['id', 'emoji' => '🛒', 'img' => null, 'glow' => '0,245,155', 'name', 'note', 'price', 'oldPrice' => null, 'tag', 'tagClass' => 'text-zinc-500'])

<div class="wishlist-item group relative p-4 rounded-2xl bg-surface-obsidian/60 border border-white/5 hover:border-primary/40 transition-all" data-product-id="{{ $id }}" data-price="{{ $price }}">
    <div class="flex items-start gap-4">
        <div class="w-16 h-16 rounded-xl bg-surface-carbon border border-white/10 flex items-center justify-center text-3xl flex-shrink-0 overflow-hidden group-hover:scale-105 transition-transform shadow-[0_0_15px_rgba({{ $glow }},0.08)]">
            @if ($img)
                <img class="item-thumb w-full h-full object-contain p-1.5" src="{{ $img }}" alt="{{ $name }}" loading="lazy">
            @endif
            <span class="item-emoji {{ $img ? 'hidden' : '' }}">{{ $emoji }}</span>
        </div>
        <div class="flex-1 min-w-0 pr-6">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1 text-[9px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span> In Stock
                </span>
                <span class="text-[10px] {{ $tagClass }} font-mono">{{ $tag }}</span>
            </div>
            <h4 class="font-display font-bold text-sm text-text-crisp truncate">{{ $name }}</h4>
            <p class="text-xs text-text-muted font-sans mt-0.5">{{ $note }}</p>
            <div class="flex items-center justify-between mt-3">
                <div class="flex items-baseline gap-1.5 font-display">
                    <span class="text-base font-black text-text-crisp">${{ $price }}</span>
                    @if ($oldPrice)
                        <span class="text-[11px] text-zinc-500 line-through">${{ $oldPrice }}</span>
                    @endif
                </div>
                <button class="add-cart-btn px-3 py-1 rounded-full bg-primary text-black font-display font-bold text-xs uppercase tracking-wider hover:bg-emerald-400 transition-all flex items-center gap-1 shadow-[0_0_10px_rgba(0,245,155,0.25)]">
                    <i class="bi bi-cart-plus-fill"></i> + Add
                </button>
            </div>
        </div>
    </div>
    <button class="remove-wishlist-item absolute top-3 right-3 w-6 h-6 rounded-md text-zinc-500 hover:text-red-400 hover:bg-red-500/10 flex items-center justify-center transition-colors" title="Remove item">
        <i class="bi bi-x-lg text-xs"></i>
    </button>
</div>
