@props(['name', 'meta', 'img', 'alt' => ''])

<div class="bg-surface-slate/60 hover:bg-surface-slate transition-all p-space-sm rounded-lg flex items-center gap-space-sm">
    <div class="w-12 h-12 rounded-lg bg-surface-obsidian overflow-hidden shrink-0">
        <img class="w-full h-full object-cover" alt="{{ $alt }}" src="{{ $img }}"/>
    </div>
    <div class="min-w-0">
        <div class="font-label-md text-label-md text-text-crisp truncate font-medium">{{ $name }}</div>
        <div class="font-body-sm text-body-sm text-text-muted">{{ $meta }}</div>
    </div>
</div>
