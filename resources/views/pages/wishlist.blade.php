@extends('layouts.app')

@section('title', 'Favorites - FoodMart')

@section('content')
<x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Favorites', 'url' => null]]" />

<header class="mt-6 mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">Saved favorites</h1>
        <p class="mt-1 text-sm text-text-muted">{{ $products->count() }} {{ $products->count() === 1 ? 'product' : 'products' }}</p>
    </div>

    @if ($products->isNotEmpty())
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('wishlist.move') }}">
                @csrf
                <button type="submit" class="water-ripple-target rounded-full bg-primary hover:bg-emerald-400 px-5 py-2.5 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Move all to cart</button>
            </form>
            <form method="POST" action="{{ route('wishlist.clear') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-full border border-border-glass px-5 py-2.5 text-xs font-mono text-text-muted hover:text-red-400 hover:border-red-400/40 transition-colors">Clear</button>
            </form>
        </div>
    @endif
</header>

@if ($products->isNotEmpty())
    <x-product-grid :products="$products" />
@else
    <div class="dark-glass-card rounded-3xl p-12 text-center">
        <p class="font-display font-bold text-xl text-text-crisp">Nothing saved yet</p>
        <p class="mt-2 text-sm text-text-muted">Tap the heart on any product to keep it here.</p>
        <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black hover:bg-emerald-400 transition-colors">Explore catalog</a>
    </div>
@endif
@endsection
