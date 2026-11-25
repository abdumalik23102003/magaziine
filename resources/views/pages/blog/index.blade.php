@extends('layouts.app')

@section('title', 'Blog - FoodMart')

@section('content')
<x-breadcrumbs :items="$breadcrumbs" />

<header class="mt-6 mb-6">
    <span class="text-xs font-mono font-bold uppercase tracking-widest text-primary">From the journal</span>
    <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">Fresh Reads</h1>
    <p class="mt-1 text-sm text-text-muted">{{ $posts->total() }} {{ $posts->total() === 1 ? 'post' : 'posts' }}</p>
</header>

<form method="GET" action="{{ route('blog.index') }}" role="search" class="mb-8 flex flex-col gap-3 sm:flex-row">
    <input type="search" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Search posts..."
           class="w-full flex-1 rounded-full border border-border-glass bg-surface-carbon px-5 py-2.5 text-sm text-text-crisp placeholder:text-text-muted focus:border-primary/60 focus:outline-none">
    @if ($brands->isNotEmpty())
        <select name="brand" onchange="this.form.submit()" aria-label="Filter by brand"
                class="rounded-full border border-border-glass bg-surface-carbon px-5 py-2.5 text-sm text-text-crisp focus:border-primary/60 focus:outline-none">
            <option value="">All brands</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->slug }}" @selected($filters['brand'] === $brand->slug)>{{ $brand->name }}</option>
            @endforeach
        </select>
    @endif
    <button type="submit" class="rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black hover:bg-emerald-400 transition-colors">Search</button>
    @if ($filters['q'] || $filters['brand'])
        <a href="{{ route('blog.index') }}" class="inline-flex items-center justify-center rounded-full border border-border-glass px-5 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-text-muted hover:text-text-crisp transition-colors">Clear</a>
    @endif
</form>

@if ($posts->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($posts as $post)
            <x-blog-card :post="$post" />
        @endforeach
    </div>

    {{ $posts->links() }}
@else
    <div class="dark-glass-card rounded-3xl p-12 text-center">
        <p class="font-display font-bold text-xl text-text-crisp">{{ $filters['q'] || $filters['brand'] ? 'No posts match your search' : 'No posts yet' }}</p>
        <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black hover:bg-emerald-400 transition-colors">Browse the catalog</a>
    </div>
@endif
@endsection
