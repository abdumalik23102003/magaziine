@extends('layouts.app')

@section('title', $post->title.' - Blog - FoodMart')

@section('content')
<x-breadcrumbs :items="$breadcrumbs" />

<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

@if ($preview)
    <div class="mx-auto mt-6 max-w-3xl rounded-2xl border border-amber-400/40 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
        Preview: this post is <strong>{{ $post->status->getLabel() }}</strong> and is not visible to the public.
        @if ($post->rejection_reason)
            <span class="mt-1 block">Reason: {{ $post->rejection_reason }}</span>
        @endif
    </div>
@endif

<article class="mx-auto mt-6 max-w-3xl">
    <header>
        <div class="flex flex-wrap items-center gap-2 text-[11px] font-mono font-bold uppercase tracking-widest">
            @if ($post->tag)
                <span class="text-primary">{{ $post->tag }}</span>
                <span class="text-zinc-600">•</span>
            @endif
            <time datetime="{{ ($post->published_at ?? $post->created_at)->toIso8601String() }}" class="text-zinc-400">{{ ($post->published_at ?? $post->created_at)->format('d M Y') }}</time>
            <span class="text-zinc-600">•</span>
            <span class="text-zinc-400">{{ $post->reading_minutes }} min read</span>
            @unless ($preview)
                <span class="text-zinc-600">•</span>
                <span class="text-zinc-400">{{ number_format((int) $post->views) }} {{ (int) $post->views === 1 ? 'view' : 'views' }}</span>
            @endunless
        </div>

        <h1 class="mt-3 font-display text-3xl font-extrabold leading-tight text-text-crisp sm:text-5xl">{{ $post->title }}</h1>

        @if ($post->excerpt)
            <p class="mt-3 text-base text-text-muted sm:text-lg">{{ $post->excerpt }}</p>
        @endif

        @if ($post->brand)
            <a href="{{ route('brands.show', $post->brand) }}" class="mt-4 inline-flex items-center gap-2 rounded-full border border-border-glass bg-surface-carbon px-4 py-1.5 text-xs font-display font-bold uppercase tracking-wider text-text-crisp transition-colors hover:border-primary/50 hover:text-primary">
                By {{ $post->brand->name }} <i class="bi bi-arrow-right"></i>
            </a>
        @endif
    </header>

    @if ($post->cover_url)
        <img src="{{ $post->cover_url }}" alt="" class="mt-8 aspect-[16/9] w-full rounded-3xl border border-border-glass object-cover">
    @endif

    {{-- Markdown HTML Post::body_html da tozalangan (xom HTML va javascript: havolalar olib tashlanadi). --}}
    <div class="mt-8 space-y-4 text-base leading-relaxed text-text-muted
                [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-primary/50 [&_blockquote]:pl-4
                [&_h2]:mt-8 [&_h2]:font-display [&_h2]:text-2xl [&_h2]:font-extrabold [&_h2]:text-text-crisp
                [&_h3]:mt-6 [&_h3]:font-display [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-text-crisp
                [&_ol]:list-decimal [&_ol]:pl-6 [&_strong]:text-text-crisp [&_ul]:list-disc [&_ul]:pl-6">
        {{ $post->body_html }}
    </div>
</article>

@if (count($related) > 0)
    <section class="mt-16">
        <x-section-heading eyebrow="Keep reading" title="More from the journal" :href="route('blog.index')" cta="All posts" />
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($related as $item)
                <x-blog-card :post="$item" />
            @endforeach
        </div>
    </section>
@endif
@endsection
