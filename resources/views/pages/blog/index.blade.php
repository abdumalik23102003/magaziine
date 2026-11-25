@extends('layouts.app')

@section('title', 'Blog - FoodMart')

@section('content')
<x-breadcrumbs :items="$breadcrumbs" />

<header class="mt-6 mb-8">
    <span class="text-xs font-mono font-bold uppercase tracking-widest text-primary">From the journal</span>
    <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">Fresh Reads</h1>
    <p class="mt-1 text-sm text-text-muted">{{ $posts->total() }} {{ $posts->total() === 1 ? 'post' : 'posts' }}</p>
</header>

@if ($posts->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($posts as $post)
            <x-blog-card :post="$post" />
        @endforeach
    </div>

    {{ $posts->links() }}
@else
    <div class="dark-glass-card rounded-3xl p-12 text-center">
        <p class="font-display font-bold text-xl text-text-crisp">No posts yet</p>
        <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black hover:bg-emerald-400 transition-colors">Browse the catalog</a>
    </div>
@endif
@endsection
