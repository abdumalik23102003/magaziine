@props(['name', 'img', 'count' => null, 'href' => '#'])

<a href="{{ $href }}" class="water-ripple-target dark-glass-card group rounded-2xl p-5 flex flex-col items-center text-center gap-3 hover:border-primary/50 transition-all cursor-pointer">
    <div class="w-16 h-16 rounded-2xl bg-surface-obsidian/80 border border-border-glass group-hover:border-primary/40 flex items-center justify-center transition-all group-hover:scale-105">
        <img src="{{ $img }}" alt="" class="w-9 h-9 object-contain" loading="lazy">
    </div>
    <div>
        <h3 class="font-display font-bold text-sm text-text-crisp group-hover:text-primary transition-colors">{{ $name }}</h3>
        @if ($count !== null)
            <p class="text-[11px] font-mono text-text-muted mt-0.5">{{ $count }} {{ $count === 1 ? 'item' : 'items' }}</p>
        @endif
    </div>
</a>
