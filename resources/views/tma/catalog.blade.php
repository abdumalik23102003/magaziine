@extends('tma.layout')

@section('content')
<p class="muted small">Hi, {{ auth()->user()->name }}</p>

<form method="GET" action="{{ route('tma.home') }}" class="search" role="search">
    <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search products" aria-label="Search products">
    @if ($category)
        <input type="hidden" name="category" value="{{ $category->slug }}">
    @endif
    @if ($discounted)
        <input type="hidden" name="discounted" value="1">
    @endif
    <button type="submit" class="btn">Search</button>
</form>

<div class="chips">
    <a href="{{ route('tma.home') }}" class="{{ ! $category && ! $discounted ? 'on' : '' }}">All</a>
    <a href="{{ route('tma.home', ['discounted' => 1]) }}" class="{{ $discounted ? 'on' : '' }}">Deals %</a>
    @foreach ($categories as $item)
        <a href="{{ route('tma.home', ['category' => $item->slug]) }}" class="{{ $category?->id === $item->id ? 'on' : '' }}">{{ $item->name }}</a>
    @endforeach
</div>

@if ($products->isEmpty())
    <div class="center">
        <p>Nothing found.</p>
        <a href="{{ route('tma.home') }}" class="btn">Show all products</a>
    </div>
@else
    <div class="grid">
        @foreach ($products as $product)
            @php($variant = $product->cardVariant())
            @continue(! $variant)

            <article class="card">
                <a href="{{ route('tma.products.show', $product->slug) }}" class="thumb">
                    @if ($product->mainImage)
                        <img src="{{ $product->mainImage->url }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <span aria-hidden="true">🛒</span>
                    @endif
                </a>
                <h3><a href="{{ route('tma.products.show', $product->slug) }}">{{ $product->name }}</a></h3>
                <span class="muted small">{{ $variant->name }}</span>
                <div>
                    <span class="price">${{ $variant->final_price }}</span>
                    @if ($variant->has_discount)
                        <span class="old">${{ $variant->price }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('cart.items.store') }}" data-ajax>
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                    <input type="hidden" name="quantity" value="1">
                    @if ((float) $variant->stock >= 1)
                        <button type="submit" class="btn btn-small">Add</button>
                    @else
                        <button type="button" class="btn btn-small" disabled>Sold out</button>
                    @endif
                </form>
            </article>
        @endforeach
    </div>

    @if ($products->hasPages())
        <div class="pager">
            @if ($products->previousPageUrl())
                <a href="{{ $products->previousPageUrl() }}" class="btn btn-ghost">Previous</a>
            @else
                <span></span>
            @endif
            @if ($products->nextPageUrl())
                <a href="{{ $products->nextPageUrl() }}" class="btn btn-ghost">Next</a>
            @endif
        </div>
    @endif
@endif
@endsection
