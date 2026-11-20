{{-- Title row for right-column widgets: icon + title on the left, optional $slot (badge) on the right --}}
@props(['icon', 'iconClass' => 'text-accent-hyper-emerald', 'title'])

<div class="flex items-center justify-between pb-space-sm border-b border-surface-container-high/40">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined {{ $iconClass }} text-[20px]">{{ $icon }}</span>
        <h3 class="font-title text-title text-text-crisp font-semibold">{{ $title }}</h3>
    </div>
    {{ $slot }}
</div>
