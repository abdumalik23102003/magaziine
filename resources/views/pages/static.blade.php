@extends('layouts.app')

@section('title', $page['title'].' - FoodMart')

@section('content')
<x-breadcrumbs :items="$breadcrumbs" />

<article class="mx-auto mt-6 max-w-3xl">
    <header>
        <p class="text-[11px] font-mono font-bold uppercase tracking-widest text-primary">FoodMart</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold leading-tight text-text-crisp sm:text-5xl">{{ $page['title'] }}</h1>
        <p class="mt-3 text-base text-text-muted sm:text-lg">{{ $page['intro'] }}</p>
        <p class="mt-3 text-xs font-mono text-zinc-500">Last updated: {{ $updatedAt }}</p>
    </header>

    <div class="mt-10 space-y-6">
        @foreach ($page['sections'] as $section)
            <section class="dark-glass-card rounded-3xl p-6">
                <h2 class="font-display text-xl font-bold text-text-crisp">{{ $section['heading'] }}</h2>

                @foreach ($section['paragraphs'] ?? [] as $paragraph)
                    <p class="mt-3 text-sm leading-relaxed text-text-muted">{{ $paragraph }}</p>
                @endforeach

                @if (! empty($section['items']))
                    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-text-muted marker:text-primary">
                        @foreach ($section['items'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>

    <nav class="mt-10 flex flex-wrap gap-3" aria-label="Other policies">
        @foreach ($others as $other)
            <a href="{{ $other['url'] }}" class="rounded-full border border-border-glass bg-surface-carbon px-4 py-1.5 text-xs font-display font-bold uppercase tracking-wide text-text-crisp transition-colors hover:border-primary hover:text-primary">{{ $other['title'] }}</a>
        @endforeach
    </nav>
</article>
@endsection
