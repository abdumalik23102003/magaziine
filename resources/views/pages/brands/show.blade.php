@extends('layouts.app')

@section('title', $brand->name.' - Brands - FoodMart')

@if ($story)
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/brand-story.css') }}">
    @endpush

    @section('before_main')
        <x-brand-story :story="$story" :name="$brand->name" />
    @endsection

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
        <script src="{{ asset('js/pages/brand-story.js') }}"></script>
    @endpush
@endif

@section('content')
<x-breadcrumbs :items="$breadcrumbs" />

<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

<section class="relative mt-6 overflow-hidden rounded-3xl border border-border-glass bg-surface-carbon">
    @if ($brand->cover_url)
        <img src="{{ $brand->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40">
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-surface-obsidian via-surface-obsidian/70 to-transparent"></div>

    <div class="relative z-10 flex flex-col gap-8 p-6 pt-32 sm:p-10 sm:pt-44 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end">
            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-glass bg-surface-obsidian shadow-xl">
                @if ($brand->logo_url)
                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }} logo" class="h-full w-full object-contain p-3">
                @else
                    <span class="font-display text-4xl font-black text-primary">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <span class="text-xs font-mono font-bold uppercase tracking-widest text-primary">Brand</span>
                <h1 class="font-display text-3xl font-extrabold text-text-crisp sm:text-5xl">{{ $brand->name }}</h1>
                @if ($brand->tagline)
                    <p class="mt-1 text-base text-text-muted sm:text-lg">{{ $brand->tagline }}</p>
                @endif
            </div>
        </div>

        <dl class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-2xl border border-border-glass bg-surface-obsidian/70 px-5 py-3 backdrop-blur">
                <dt class="text-[10px] font-mono uppercase tracking-widest text-text-muted">Products</dt>
                <dd class="font-display text-2xl font-black text-text-crisp">{{ $stats['products'] }}</dd>
            </div>
            <div class="rounded-2xl border border-border-glass bg-surface-obsidian/70 px-5 py-3 backdrop-blur">
                <dt class="text-[10px] font-mono uppercase tracking-widest text-text-muted">Categories</dt>
                <dd class="font-display text-2xl font-black text-text-crisp">{{ $stats['categories'] }}</dd>
            </div>
            <div class="rounded-2xl border border-border-glass bg-surface-obsidian/70 px-5 py-3 backdrop-blur">
                <dt class="text-[10px] font-mono uppercase tracking-widest text-text-muted">On sale</dt>
                <dd class="font-display text-2xl font-black text-accent-lime">{{ $stats['deals'] }}</dd>
            </div>
        </dl>
    </div>

    @if ($brand->description || $brand->website_url)
        <div class="relative z-10 flex flex-col gap-4 px-6 pb-8 sm:flex-row sm:items-start sm:justify-between sm:px-10">
            @if ($brand->description)
                <p class="max-w-3xl whitespace-pre-line text-sm leading-relaxed text-text-muted">{{ $brand->description }}</p>
            @endif
            @if ($brand->website_url)
                <a href="{{ $brand->website_url }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex shrink-0 items-center gap-2 rounded-full border border-primary/40 bg-primary/5 px-5 py-2 text-xs font-display font-bold uppercase tracking-wider text-primary transition-all hover:bg-primary hover:text-black">
                    Visit website <i class="bi bi-box-arrow-up-right"></i>
                </a>
            @endif
        </div>
    @endif
</section>

<section class="mt-12">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h2 class="font-display text-2xl font-extrabold text-text-crisp">Products by {{ $brand->name }}</h2>

        <form method="GET" action="{{ route('brands.show', $brand) }}" class="flex items-center gap-2">
            <label for="sort" class="text-xs font-mono uppercase tracking-wider text-text-muted">Sort by</label>
            <select id="sort" name="sort" onchange="this.form.submit()" class="rounded-full border border-border-glass bg-surface-carbon py-2 pl-4 pr-8 text-xs text-text-crisp focus:border-primary focus:outline-none">
                @foreach ($sorts as $key => $name)
                    <option value="{{ $key }}" @selected($sort === $key)>{{ $name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($products->isNotEmpty())
        <x-product-grid :products="$products" />

        {{ $products->links() }}
    @else
        <div class="dark-glass-card rounded-3xl p-12 text-center">
            <p class="font-display text-xl font-bold text-text-crisp">No products yet</p>
            <p class="mt-2 text-sm text-text-muted">This brand has nothing in stock right now.</p>
            <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black transition-colors hover:bg-emerald-400">Browse the catalog</a>
        </div>
    @endif
</section>

@if (count($posts) > 0)
    <section class="mt-16">
        <x-section-heading eyebrow="From the journal" :title="'Latest from '.$brand->name" :href="route('blog.index')" cta="All posts" />
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($posts as $post)
                <x-blog-card :post="$post" />
            @endforeach
        </div>
    </section>
@endif
@endsection
