@props(['eyebrow', 'title', 'sub' => null, 'href' => null, 'cta' => null])

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-8">
    <div>
        <span class="text-xs font-mono font-bold tracking-widest uppercase text-primary">{{ $eyebrow }}</span>
        <h2 class="font-display font-extrabold text-2xl sm:text-3xl text-text-crisp mt-1">{{ $title }}</h2>
        @if ($sub)
            <p class="text-sm text-text-muted mt-1">{{ $sub }}</p>
        @endif
    </div>
    @if ($href && $cta)
        <a class="water-ripple-target px-5 py-2 rounded-full border border-primary/40 hover:border-primary bg-primary/5 hover:bg-primary text-primary hover:text-black font-display text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer" href="{{ $href }}">
            <span>{{ $cta }}</span> <i class="bi bi-arrow-right"></i>
        </a>
    @endif
</div>
