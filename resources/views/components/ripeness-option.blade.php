@props(['title', 'sub', 'dot' => 'bg-outline', 'active' => false])

<button type="button" class="ripeness-btn p-space-sm rounded-lg text-left transition-all {{ $active ? 'active bg-surface-elevated ring-1 ring-accent-hyper-emerald shadow-sm' : 'bg-surface-slate hover:bg-surface-elevated' }}">
    <div class="flex items-center gap-1.5 font-label-md text-label-md font-semibold {{ $active ? 'text-accent-hyper-emerald' : 'text-text-crisp' }}">
        <span class="w-2 h-2 rounded-full {{ $dot }}"></span>{{ $title }}
    </div>
    <div class="font-body-sm text-body-sm text-text-muted mt-1">{{ $sub }}</div>
</button>
