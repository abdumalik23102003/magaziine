@props(['label', 'icon', 'iconClass' => 'text-accent-hyper-emerald', 'value', 'valueClass' => 'text-text-crisp', 'caption', 'captionClass' => 'text-accent-hyper-emerald', 'text', 'bar' => null, 'barClass' => 'bg-accent-hyper-emerald', 'footer' => null])

<div class="bg-surface-carbon p-space-lg rounded-xl shadow-md flex flex-col justify-between">
    <div>
        <div class="flex justify-between items-center text-text-muted">
            <span class="font-label-sm text-label-sm uppercase tracking-wider">{{ $label }}</span>
            <span class="material-symbols-outlined {{ $iconClass }} text-[20px]">{{ $icon }}</span>
        </div>
        <div class="font-display text-display {{ $valueClass }} font-bold mt-space-sm">{{ $value }}</div>
        <div class="font-label-md text-label-md {{ $captionClass }} font-semibold uppercase mt-1">{{ $caption }}</div>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-space-xs">{{ $text }}</p>
    </div>
    @if ($bar !== null)
        <div class="w-full bg-surface-slate h-1.5 rounded-full mt-space-md overflow-hidden"><div class="{{ $barClass }} h-full rounded-full" style="width: {{ $bar }}%;"></div></div>
    @elseif ($footer)
        <div class="flex items-center gap-1.5 text-accent-hyper-emerald font-label-sm text-label-sm uppercase mt-space-md pt-space-xs"><span class="w-2 h-2 rounded-full bg-accent-hyper-emerald"></span>{{ $footer }}</div>
    @endif
</div>
