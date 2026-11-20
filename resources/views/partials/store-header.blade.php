@php
    // Replace '#' with route('...') once those pages exist.
    $storeNav = [
        ['label' => 'Departments',       'href' => '#'],
        ['label' => 'Biodynamic Vault',  'href' => '#'],
        ['label' => 'Traceability',      'href' => '#'],
    ];
@endphp
<header class="fixed top-0 left-0 right-0 z-50 bg-surface-obsidian/85 backdrop-blur-2xl shadow-[0_1px_12px_rgba(0,0,0,0.4)]">
    <div class="h-20 max-w-[1600px] mx-auto px-margin-mobile md:px-margin flex items-center justify-between gap-gutter">
        <div class="flex items-center gap-space-lg">
            <a class="flex items-center gap-space-xs group" href="{{ url('/') }}">
                <span class="font-headline-md text-headline-md tracking-tight uppercase text-text-crisp font-semibold">FoodMart</span>
                <span class="w-2 h-2 rounded-full bg-accent-hyper-emerald shadow-[0_0_10px_#00f59b]"></span>
            </a>
            <nav class="hidden xl:flex items-center gap-space-md">
                @foreach ($storeNav as $item)
                    <a class="font-label-lg text-label-lg uppercase tracking-wider text-on-surface-variant hover:text-on-surface transition-colors py-space-xs px-space-sm" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>

        <div class="flex-1 max-w-md hidden md:block">
            <div class="relative flex items-center bg-surface-slate rounded-lg px-space-md py-space-xs">
                <span class="material-symbols-outlined text-outline text-[18px] mr-space-sm">search</span>
                <input class="w-full bg-transparent font-body-sm text-body-sm text-text-crisp placeholder:text-text-muted focus:outline-none" placeholder="Search ultra-pure biologicals, botanicals..." type="text"/>
                <div class="flex items-center gap-1 bg-surface-elevated px-1.5 py-0.5 rounded text-outline font-label-sm text-label-sm"><span>⌘</span><span>K</span></div>
            </div>
        </div>

        <div class="flex items-center gap-space-md">
            <div class="hidden lg:flex items-center gap-space-xs bg-surface-carbon px-space-md py-space-xs rounded-full">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-electric-lime opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-accent-electric-lime"></span>
                </span>
                <span class="font-label-md text-label-md tracking-wider uppercase text-on-surface-variant">24/7 Concierge</span>
            </div>
            <a class="flex items-center justify-center w-10 h-10 rounded-lg bg-surface-slate text-on-surface-variant hover:text-accent-hyper-emerald hover:bg-surface-elevated transition-colors" href="#" title="Wishlist">
                <span class="material-symbols-outlined text-[20px]">favorite</span>
            </a>
            <a class="flex items-center justify-center p-0.5 rounded-full ring-2 ring-accent-hyper-emerald/80 shadow-[0_0_12px_rgba(0,245,155,0.3)]" href="{{ route('profile') }}" title="My profile">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center"><span class="material-symbols-outlined text-on-primary text-[18px]">person</span></div>
            </a>
        </div>
    </div>
</header>
