@props(['img', 'kicker', 'title', 'cta' => 'Shop now', 'href' => '#', 'glow' => '0,245,155'])

<a href="{{ $href }}" class="water-ripple-target group relative overflow-hidden rounded-3xl border border-border-glass bg-gradient-to-br from-surface-carbon to-surface-obsidian p-6 min-h-[220px] flex items-center justify-between gap-4 hover:border-primary/50 transition-all cursor-pointer">
    <div class="absolute -right-10 -bottom-10 w-52 h-52 rounded-full blur-3xl pointer-events-none" style="background: rgba({{ $glow }}, 0.18)"></div>
    <div class="relative z-10 max-w-[55%]">
        <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-accent-lime">{{ $kicker }}</span>
        <h3 class="font-display font-extrabold text-xl sm:text-2xl text-text-crisp leading-tight mt-1 mb-4">{{ $title }}</h3>
        <span class="inline-flex items-center gap-1.5 text-xs font-display font-bold uppercase tracking-wider text-primary group-hover:gap-3 transition-all">
            {{ $cta }} <i class="bi bi-arrow-right"></i>
        </span>
    </div>
    <img src="{{ asset($img) }}" alt="{{ $title }}" loading="lazy"
         class="relative z-10 w-40 sm:w-44 max-h-44 object-contain drop-shadow-[0_16px_24px_rgba(0,0,0,0.5)] group-hover:scale-105 group-hover:-rotate-2 transition-transform duration-500">
</a>
