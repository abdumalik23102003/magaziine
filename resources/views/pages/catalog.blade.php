@extends('layouts.app')

@section('title', ($category ? $category->name : ($search !== '' ? 'Search' : 'Catalog')).' - FoodMart')

@section('content')
@php
    $action = $category ? route('catalog.category', $category->slug) : route('catalog.index');
    $hasFilters = $search !== '' || $brand || $discounted;

    $heading = $category
        ? $category->name
        : ($search !== '' ? 'Results for "'.$search.'"' : 'All products');

    $field = 'w-full bg-surface-carbon border border-border-glass hover:border-primary/40 focus:border-primary rounded-xl py-2.5 px-4 text-sm text-text-crisp placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all';
    $label = 'block text-[10px] font-mono font-bold uppercase tracking-widest text-primary mb-2';
    $linkOn = 'flex items-center justify-between rounded-lg px-3 py-2 bg-primary/10 text-primary font-bold';
    $linkOff = 'flex items-center justify-between rounded-lg px-3 py-2 text-text-muted hover:bg-white/5 hover:text-text-crisp transition-colors';
    $chip = 'inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/5 px-3 py-1 text-xs font-mono text-primary hover:bg-primary hover:text-black transition-colors';
@endphp

<x-breadcrumbs :items="$breadcrumbs" />

<header class="mt-6 mb-8">
    <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">{{ $heading }}</h1>
    <p class="mt-1 text-sm text-text-muted">
        {{ $products->total() }} {{ $products->total() === 1 ? 'product' : 'products' }} found
    </p>
</header>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    {{-- Filtrlar --}}
    <aside class="lg:col-span-3 space-y-6">
        <form id="filters" method="GET" action="{{ $action }}" class="dark-glass-card rounded-2xl p-5 space-y-5">
            <div>
                <label for="q" class="{{ $label }}">Search</label>
                <input id="q" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search products..." class="{{ $field }}">
            </div>

            <div>
                <label for="brand" class="{{ $label }}">Brand</label>
                <select id="brand" name="brand" class="{{ $field }}" onchange="this.form.submit()">
                    <option value="">All brands</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b->slug }}" @selected($brand && $brand->id === $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <label class="flex items-center gap-3 cursor-pointer text-sm text-text-crisp">
                <input type="checkbox" name="discounted" value="1" @checked($discounted) onchange="this.form.submit()" class="h-4 w-4 rounded border-border-glass bg-surface-carbon text-primary focus:ring-primary/30">
                Only discounted
            </label>

            <div class="flex items-center gap-2">
                <button type="submit" class="water-ripple-target flex-1 rounded-full bg-primary hover:bg-emerald-400 text-black font-display font-bold text-xs uppercase tracking-wider py-2.5 transition-colors">Apply</button>
                @if ($hasFilters)
                    <a href="{{ $action }}" class="rounded-full border border-border-glass px-4 py-2.5 text-xs font-mono text-text-muted hover:text-text-crisp hover:border-primary/40 transition-colors">Reset</a>
                @endif
            </div>
        </form>

        <nav aria-label="Categories" class="dark-glass-card rounded-2xl p-5">
            <h2 class="{{ $label }}">Categories</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="{{ route('catalog.index') }}" class="{{ $category ? $linkOff : $linkOn }}">All products</a></li>
                @foreach ($navCategories as $root)
                    @php
                        $isActive = $category && $category->id === $root->id;
                        $inBranch = $category && ($isActive || $category->parent_id === $root->id);
                    @endphp
                    <li>
                        <a href="{{ route('catalog.category', $root->slug) }}" class="{{ $isActive ? $linkOn : $linkOff }}">{{ $root->name }}</a>
                        @if ($inBranch && $root->children->isNotEmpty())
                            <ul class="ml-3 mt-1 space-y-1 border-l border-border-glass pl-3">
                                @foreach ($root->children as $child)
                                    <li><a href="{{ route('catalog.category', $child->slug) }}" class="{{ $category && $category->id === $child->id ? $linkOn : $linkOff }}">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    </aside>

    {{-- Natijalar --}}
    <section class="lg:col-span-9">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                @if ($search !== '')
                    <a href="{{ request()->fullUrlWithoutQuery(['q', 'page']) }}" class="{{ $chip }}">"{{ $search }}" <span aria-hidden="true">&times;</span><span class="sr-only">remove search filter</span></a>
                @endif
                @if ($brand)
                    <a href="{{ request()->fullUrlWithoutQuery(['brand', 'page']) }}" class="{{ $chip }}">{{ $brand->name }} <span aria-hidden="true">&times;</span><span class="sr-only">remove brand filter</span></a>
                @endif
                @if ($discounted)
                    <a href="{{ request()->fullUrlWithoutQuery(['discounted', 'page']) }}" class="{{ $chip }}">Discounted <span aria-hidden="true">&times;</span><span class="sr-only">remove discount filter</span></a>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <label for="sort" class="text-xs font-mono uppercase tracking-wider text-text-muted">Sort by</label>
                <select id="sort" name="sort" form="filters" onchange="this.form.submit()" class="bg-surface-carbon border border-border-glass rounded-full py-2 pl-4 pr-8 text-xs text-text-crisp focus:outline-none focus:border-primary">
                    @foreach ($sorts as $key => $name)
                        <option value="{{ $key }}" @selected($sort === $key)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($products->isNotEmpty())
            <x-product-grid :products="$products" cols="lg:grid-cols-3" />

            {{ $products->links() }}
        @else
            <div class="dark-glass-card rounded-3xl p-12 text-center">
                <p class="font-display font-bold text-xl text-text-crisp">Nothing found</p>
                <p class="mt-2 text-sm text-text-muted">Try a different search or remove some filters.</p>
                <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black hover:bg-emerald-400 transition-colors">Show all products</a>
            </div>
        @endif
    </section>
</div>
@endsection
