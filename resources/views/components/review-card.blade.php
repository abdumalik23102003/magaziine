@props(['name', 'badge', 'rating' => 5, 'text', 'meta'])

<div class="bg-surface-slate/60 p-space-md rounded-xl flex flex-col justify-between">
    <div>
        <div class="flex items-center justify-between">
            <span class="font-title text-title text-text-crisp font-semibold">{{ $name }}</span>
            <span class="px-2 py-0.5 rounded bg-surface-elevated text-accent-hyper-emerald font-label-sm text-label-sm uppercase">{{ $badge }}</span>
        </div>
        <x-star-rating :rating="$rating" :size="16" class="my-space-xs"/>
        <p class="font-body-sm text-body-sm text-on-surface-variant">"{{ $text }}"</p>
    </div>
    <div class="font-label-sm text-label-sm text-text-muted uppercase mt-space-md pt-space-xs">{{ $meta }}</div>
</div>
