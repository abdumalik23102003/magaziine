@props(['id', 'emoji' => '🛒', 'img' => null, 'glow' => '0,245,155', 'name', 'price', 'unit' => 'each', 'qty' => 1, 'badge' => 'In Stock', 'badgeTone' => 'primary', 'pulse' => false, 'note' => ''])

@php
    $badgeClass = $badgeTone === 'lime'
        ? 'bg-accent-lime/15 text-accent-lime border border-accent-lime/30'
        : 'bg-primary/10 text-primary border border-primary/20';
    $lineTotal = number_format((float) $price * (int) $qty, 2);
@endphp

<div class="cart-item group relative p-4 rounded-2xl bg-surface-obsidian/75 border border-white/10 hover:border-primary/40 transition-all" data-price="{{ $price }}" data-product-id="{{ $id }}">
    <div class="flex items-start gap-4">
        <div class="w-16 h-16 rounded-xl bg-surface-carbon border border-white/10 flex items-center justify-center text-3xl flex-shrink-0 overflow-hidden group-hover:scale-105 transition-transform shadow-[0_0_15px_rgba({{ $glow }},0.08)]">
            @if ($img)
                <img class="item-thumb w-full h-full object-contain p-1.5" src="{{ $img }}" alt="{{ $name }}" loading="lazy">
            @endif
            <span class="item-emoji {{ $img ? 'hidden' : '' }}">{{ $emoji }}</span>
        </div>
        <div class="flex-1 min-w-0 pr-6">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1 text-[9px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $badgeClass }}">
                    @if ($pulse)
                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                    @endif
                    {{ $badge }}
                </span>
                <span class="text-[10px] text-zinc-400 font-mono">{{ $note }}</span>
            </div>
            <h4 class="font-display font-bold text-sm text-text-crisp truncate">{{ $name }}</h4>
            <p class="text-xs text-text-muted font-sans mt-0.5">${{ $price }} / {{ $unit }}</p>
            <div class="flex items-center justify-between mt-3">
                <div class="inline-flex items-center rounded-lg bg-surface-carbon border border-border-glass p-0.5">
                    <button class="cart-step-btn w-6 h-6 rounded flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/5 transition-colors text-xs" data-action="decrease">
                        <i class="bi bi-dash"></i>
                    </button>
                    <span class="cart-item-qty w-7 text-center font-mono font-bold text-xs text-text-crisp">{{ $qty }}</span>
                    <button class="cart-step-btn w-6 h-6 rounded flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/5 transition-colors text-xs" data-action="increase">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                <div class="font-display font-black text-sm text-text-crisp cart-item-total">${{ $lineTotal }}</div>
            </div>
        </div>
    </div>
    <button class="cart-remove-item absolute top-3 right-3 w-6 h-6 rounded-md text-zinc-500 hover:text-red-400 hover:bg-red-500/10 flex items-center justify-center transition-colors" title="Remove item">
        <i class="bi bi-x-lg text-xs"></i>
    </button>
</div>
