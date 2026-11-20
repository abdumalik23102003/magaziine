@props(['name', 'meta', 'price', 'img', 'alt' => '', 'qty' => 1, 'discount' => null])

<div class="checkout-item flex items-center gap-space-sm bg-surface-obsidian/80 p-2.5 rounded-lg group hover:bg-surface-slate transition-colors" data-price="{{ $price }}" data-qty="{{ $qty }}">
    <div class="relative w-14 h-14 rounded bg-surface-slate flex-shrink-0 overflow-hidden">
        <img class="w-full h-full object-cover" alt="{{ $alt }}" src="{{ $img }}"/>
        <span class="absolute bottom-0 right-0 bg-surface-elevated/90 font-mono text-[10px] px-1 font-bold text-text-crisp">x{{ $qty }}</span>
    </div>
    <div class="flex flex-col flex-1 min-w-0">
        @if ($discount)
            <div class="flex items-center gap-1.5">
                <span class="font-label-lg text-label-lg text-text-crisp truncate">{{ $name }}</span>
                <span class="font-label-sm text-[8px] bg-secondary-container text-on-secondary px-1 py-0.5 rounded font-bold uppercase">-{{ $discount }}%</span>
            </div>
        @else
            <span class="font-label-lg text-label-lg text-text-crisp truncate">{{ $name }}</span>
        @endif
        <span class="font-body-sm text-body-sm text-text-muted">{{ $meta }}</span>
    </div>
    <div class="font-label-lg text-label-lg font-mono text-text-crisp font-semibold">${{ number_format((float) $price * (int) $qty, 2) }}</div>
</div>
