@php
    // Replace '#' with route('...') when those pages exist.
    $checkoutNav = [
        ['label' => 'Review',            'href' => '#', 'active' => false],
        ['label' => 'Checkout',          'href' => route('checkout'), 'active' => true],
        ['label' => 'Concierge Support', 'href' => '#', 'active' => false],
    ];
@endphp
<header class="fixed top-0 left-0 right-0 z-50 bg-surface-obsidian/85 backdrop-blur-2xl shadow-[0_4px_24px_rgba(0,0,0,0.6)]">
    <div class="h-20 w-full px-gutter flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-space-lg">
            <a class="group flex items-center gap-space-sm" href="{{ url('/') }}">
                <div class="w-9 h-9 rounded-lg bg-surface-slate flex items-center justify-center text-primary-container shadow-[0_0_15px_rgba(0,245,155,0.15)] group-hover:shadow-[0_0_20px_rgba(0,245,155,0.35)] transition-all">
                    <span class="material-symbols-outlined text-[20px]">nutrition</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5">
                        <span class="font-headline-sm text-headline-sm text-text-crisp tracking-tight uppercase">FOODMART</span>
                        <span class="h-1.5 w-1.5 rounded-full bg-primary-container animate-pulse"></span>
                    </div>
                    <span class="font-label-sm text-label-sm tracking-[0.14em] uppercase text-text-muted">Performance Organic</span>
                </div>
            </a>
            <div class="hidden xl:flex items-center gap-space-sm pl-space-md py-space-xs bg-surface-slate/70 px-space-md rounded-full shadow-[0_0_15px_-3px_rgba(0,245,155,0.08)]">
                <span class="material-symbols-outlined text-primary-container text-[16px]">verified_user</span>
                <span class="font-label-md text-label-md uppercase text-on-surface-variant tracking-wider">256-Bit SSL Encrypted</span>
                <span class="text-outline-variant font-label-sm text-label-sm">/</span>
                <span class="font-label-md text-label-md uppercase text-tertiary-fixed tracking-wider">Cold-Chain Guarantee 0-4°C</span>
            </div>
        </div>

        <nav class="flex items-center gap-space-md">
            @foreach ($checkoutNav as $item)
                <a href="{{ $item['href'] }}"
                   @if ($item['active']) aria-current="page" @endif
                   class="font-label-lg uppercase tracking-wider transition-colors {{ $item['active'] ? 'bg-surface-elevated text-text-crisp' : 'text-label-lg text-on-surface-variant hover:text-text-crisp' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-space-md">
            <div class="hidden md:flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-slate">
                <span class="material-symbols-outlined text-accent-electric-lime text-[16px]">support_agent</span>
                <div class="flex flex-col text-right">
                    <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-electric-lime">24/7 Priority</span>
                    <span class="font-label-md text-label-md text-text-crisp">Bio-Concierge Active</span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
            </div>
        </div>
    </div>
</header>
