@props(['post'])

{{-- $post: PostService::card() massivi --}}
<article {{ $attributes->merge(['class' => 'dark-glass-card group rounded-3xl overflow-hidden flex flex-col']) }}>
    <a href="{{ $post['url'] }}" class="block aspect-[16/10] overflow-hidden bg-gradient-to-br from-surface-slate to-surface-obsidian" tabindex="-1" aria-hidden="true">
        @if ($post['cover_url'])
            <img src="{{ $post['cover_url'] }}" alt="" loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        @endif
    </a>
    <div class="p-5 flex flex-col flex-1">
        <div class="flex items-center gap-2 text-[10px] font-mono font-bold uppercase tracking-widest mb-2">
            <span class="text-primary">{{ $post['tag'] }}</span>
            @if ($post['date'])
                <span class="text-zinc-600">•</span>
                <span class="text-zinc-400">{{ $post['date'] }}</span>
            @endif
        </div>
        <h3 class="font-display font-bold text-base text-text-crisp group-hover:text-primary transition-colors mb-2">
            <a href="{{ $post['url'] }}">{{ $post['title'] }}</a>
        </h3>
        <p class="text-sm text-text-muted leading-relaxed line-clamp-3">{{ $post['excerpt'] }}</p>
    </div>
</article>
