@props(['time', 'title', 'text', 'current' => false, 'glow' => false])

<div class="relative flex items-start gap-space-md group">
    <div class="absolute -left-[31px] md:-left-[39px] top-1.5 w-4 h-4 rounded-full {{ $current ? 'bg-accent-electric-lime animate-ping' : 'bg-accent-hyper-emerald' }} {{ $glow ? 'shadow-[0_0_12px_#00f59b]' : '' }}"></div>
    <div class="flex-1">
        <div class="flex flex-wrap items-baseline gap-space-xs">
            <span class="font-mono text-label-md font-semibold {{ $current ? 'text-accent-electric-lime' : 'text-accent-hyper-emerald' }}">{{ $time }}</span>
            <span class="font-title text-title font-semibold {{ $current ? 'text-accent-electric-lime' : 'text-text-crisp' }}">• {{ $title }}</span>
        </div>
        <p class="font-body-sm text-body-sm mt-1 {{ $current ? 'text-text-crisp' : 'text-on-surface-variant' }}">{{ $text }}</p>
    </div>
</div>
