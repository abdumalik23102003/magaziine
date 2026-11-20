@props(['label', 'icon', 'iconClass' => 'text-accent-hyper-emerald', 'value', 'subIcon' => null, 'sub', 'subClass' => 'text-text-muted'])

<div class="bg-surface-slate/80 hover:bg-surface-elevated/90 transition-all rounded-xl p-space-md flex flex-col justify-between shadow-sm relative overflow-hidden group">
    <div class="flex items-center justify-between text-text-muted mb-space-xs">
        <span class="font-label-sm text-label-sm uppercase tracking-wider">{{ $label }}</span>
        <span class="material-symbols-outlined {{ $iconClass }} text-[20px] group-hover:scale-110 transition-transform">{{ $icon }}</span>
    </div>
    <div class="flex items-end justify-between">
        <div>
            <div class="font-headline-sm text-headline-sm font-semibold text-text-crisp tracking-tight">{{ $value }}</div>
            <div class="font-body-sm text-body-sm {{ $subClass }} flex items-center gap-1 mt-0.5">
                @if ($subIcon)<span class="material-symbols-outlined text-[14px]">{{ $subIcon }}</span>@endif
                <span>{{ $sub }}</span>
            </div>
        </div>
        {{ $slot }}
    </div>
</div>
