@props(['icon', 'iconClass' => 'text-accent-hyper-emerald', 'title', 'text'])

<div class="bg-surface-carbon p-space-lg rounded-xl shadow-md">
    <div class="w-10 h-10 rounded-lg bg-surface-slate flex items-center justify-center {{ $iconClass }} mb-space-sm"><span class="material-symbols-outlined">{{ $icon }}</span></div>
    <div class="font-title text-title text-text-crisp font-semibold">{{ $title }}</div>
    <p class="font-body-sm text-body-sm text-on-surface-variant mt-space-xs">{{ $text }}</p>
</div>
