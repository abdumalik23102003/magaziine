@props(['id', 'productId' => null, 'emoji' => '🛒', 'img' => null, 'glow' => '0,245,155', 'category', 'title', 'rating' => 5, 'reviews' => 0, 'price', 'oldPrice' => null, 'badge' => null, 'href' => null])

{{-- data-product-id = VARIANT id (savatga shu qo'shiladi). data-wishlist-id = mahsulot id. --}}
<div class="dark-glass-card rounded-3xl p-5 flex flex-col group" data-product-id="{{ $id }}" data-name="{{ $title }}" data-price="{{ $price }}" data-category="{{ $category }}">
    <div class="relative w-full aspect-square rounded-2xl bg-surface-obsidian/75 border border-border-glass flex items-center justify-center text-6xl group-hover:scale-[1.02] transition-transform overflow-hidden mb-5">
        @if ($href)
            <a href="{{ $href }}" class="absolute inset-0" aria-label="{{ $title }}"></a>
        @endif
        @if ($badge)
            <span class="absolute top-3 left-3 px-2.5 py-0.5 rounded-full bg-accent-lime text-black font-mono font-black text-[11px] shadow-lg pointer-events-none">
                {{ $badge }}
            </span>
        @endif
        @if ($productId)
            <button class="wishlist-toggle water-ripple-target absolute top-3 right-3 w-8 h-8 rounded-full bg-surface-carbon/80 border border-border-glass text-zinc-400 hover:text-red-400 hover:border-red-400/40 flex items-center justify-center transition-colors" type="button" data-wishlist-id="{{ $productId }}" aria-label="Save to favorites" aria-pressed="false">
                <i class="bi bi-heart text-xs"></i>
            </button>
        @endif
        @if ($img)
            {{-- .png = cut-out on transparent background -> contain; .jpg = full photo -> cover --}}
            <img src="{{ $img }}" alt="{{ $title }}" loading="lazy"
                 class="w-full h-full {{ str_ends_with($img, '.png') ? 'object-contain p-6 drop-shadow-[0_12px_20px_rgba(0,0,0,0.45)]' : 'object-cover' }}">
        @else
            <span class="filter drop-shadow-[0_10px_20px_rgba({{ $glow }},0.2)]">{{ $emoji }}</span>
        @endif
    </div>

    <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary mb-1">{{ $category }}</span>
    <h3 class="font-display font-bold text-base text-text-crisp group-hover:text-primary transition-colors mb-2">
        @if ($href)
            <a href="{{ $href }}">{{ $title }}</a>
        @else
            {{ $title }}
        @endif
    </h3>

    <div class="flex items-center gap-1 text-accent-lime text-xs mb-4">
        @for ($i = 1; $i <= 5; $i++)
            @if ($rating >= $i)
                <i class="bi bi-star-fill"></i>
            @elseif ($rating >= $i - 0.5)
                <i class="bi bi-star-half"></i>
            @else
                <i class="bi bi-star"></i>
            @endif
        @endfor
        <span class="text-zinc-500 font-mono text-[11px] ml-1">({{ $reviews }})</span>
    </div>

    <div class="mt-auto pt-4 border-t border-border-glass flex items-center justify-between gap-2">
        <div class="flex items-baseline gap-1 font-display shrink-0">
            <span class="text-xl font-black text-text-crisp">${{ $price }}</span>
            @if ($oldPrice)
                <span class="text-xs text-zinc-500 line-through">${{ $oldPrice }}</span>
            @endif
        </div>

        <div class="flex items-center gap-1.5">
            <div class="product-qty-controller flex items-center bg-surface-carbon border border-primary/35 rounded-full p-0.5 shadow-[0_0_12px_rgba(0,245,155,0.12)]">
                <button aria-label="Decrease quantity" class="qty-btn-minus water-ripple-target w-6 h-6 rounded-full bg-surface-elevated/90 hover:bg-primary/20 text-text-crisp hover:text-primary flex items-center justify-center text-xs font-bold transition-all border border-border-glass" type="button">
                    <i class="bi bi-dash font-bold"></i>
                </button>
                <span class="qty-value font-mono font-bold text-xs text-primary min-w-[22px] text-center select-none">1</span>
                <button aria-label="Increase quantity" class="qty-btn-plus water-ripple-target w-6 h-6 rounded-full bg-primary hover:bg-emerald-400 text-surface-obsidian flex items-center justify-center text-xs font-extrabold transition-all" type="button">
                    <i class="bi bi-plus-lg font-black"></i>
                </button>
            </div>
            <button class="add-to-cart-action water-ripple-target h-7 px-2.5 rounded-full bg-primary hover:bg-emerald-400 text-surface-obsidian font-display font-bold text-[11px] uppercase tracking-wider flex items-center gap-1 transition-all shadow-[0_0_10px_rgba(0,245,155,0.35)] shrink-0" type="button">
                <i class="bi bi-bag-plus-fill text-xs"></i> <span>Add</span>
            </button>
        </div>
    </div>
</div>
