@props(['label', 'value' => '', 'type' => 'text', 'icon' => null, 'size' => 'md', 'class' => '', 'inputClass' => ''])

@php
    $labelSize = $size === 'sm' ? 'font-label-sm text-label-sm' : 'font-label-md text-label-md';
    $pad = $size === 'sm' ? 'px-3' : 'px-4';
@endphp

<div class="flex flex-col gap-1.5 {{ $class }}">
    <label class="{{ $labelSize }} uppercase tracking-wider text-on-surface-variant">{{ $label }}</label>
    @if ($icon)
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px]">{{ $icon }}</span>
            <input type="{{ $type }}" value="{{ $value }}" class="w-full bg-surface-obsidian text-text-crisp pl-11 pr-4 py-2.5 rounded-lg font-body-md text-body-md focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner"/>
        </div>
    @else
        <input type="{{ $type }}" value="{{ $value }}" class="w-full bg-surface-obsidian text-text-crisp {{ $pad }} py-2.5 rounded-lg font-body-md text-body-md focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner {{ $inputClass }}"/>
    @endif
</div>
