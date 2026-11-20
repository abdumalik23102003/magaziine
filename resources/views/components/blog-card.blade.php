@props(['img', 'tag', 'date', 'title', 'excerpt', 'href' => '#'])

<article class="dark-glass-card group rounded-3xl overflow-hidden flex flex-col">
    <a href="{{ $href }}" class="block aspect-[16/10] overflow-hidden bg-surface-obsidian">
        <img src="{{ asset($img) }}" alt="{{ $title }}" loading="lazy"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
    </a>
    <div class="p-5 flex flex-col flex-1">
        <div class="flex items-center gap-2 text-[10px] font-mono font-bold uppercase tracking-widest mb-2">
            <span class="text-primary">{{ $tag }}</span>
            <span class="text-zinc-600">•</span>
            <span class="text-zinc-400">{{ $date }}</span>
        </div>
        <h3 class="font-display font-bold text-base text-text-crisp group-hover:text-primary transition-colors mb-2">
            <a href="{{ $href }}">{{ $title }}</a>
        </h3>
        <p class="text-sm text-text-muted leading-relaxed">{{ $excerpt }}</p>
    </div>
</article>
