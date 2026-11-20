@props(['name', 'tag', 'text', 'price' => 0, 'active' => false, 'badge', 'badgeClass' => 'text-text-crisp font-semibold', 'extra' => null, 'extraClass' => 'text-text-muted', 'strike' => null])

<div class="speed-option cursor-pointer rounded-xl p-space-md relative transition-all {{ $active ? 'bg-surface-slate shadow-[0_0_25px_-5px_rgba(0,245,155,0.2)]' : 'bg-surface-carbon hover:bg-surface-slate/60 opacity-85' }}" data-price="{{ number_format((float) $price, 2, '.', '') }}" data-speed="{{ $name }}">
    <div class="flex items-center justify-between mb-space-xs">
        <span class="font-label-sm text-label-sm uppercase tracking-widest {{ $active ? 'text-primary-container font-semibold' : 'text-text-muted' }}">{{ $tag }}</span>
        <span class="material-symbols-outlined text-[18px] {{ $active ? 'text-primary-container' : 'text-text-muted' }}">{{ $active ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
    </div>
    <div class="font-headline-sm text-headline-sm text-text-crisp leading-snug mb-1">{{ $name }}</div>
    <p class="font-body-sm text-body-sm text-text-muted mb-space-sm">{{ $text }}</p>
    <div class="flex items-center justify-between pt-2 px-2 py-1 {{ $active ? 'bg-surface-carbon/40 rounded' : '' }}">
        <span class="font-label-md text-label-md {{ $badgeClass }}">{{ $badge }}</span>
        @if ($strike)
            <span class="font-label-sm text-label-sm text-text-muted line-through">{{ $strike }}</span>
        @elseif ($extra)
            <span class="font-label-sm text-label-sm font-mono {{ $extraClass }}">{{ $extra }}</span>
        @endif
    </div>
</div>
