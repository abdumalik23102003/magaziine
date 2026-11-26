@extends('tma.layout')

@section('title', $product->name)
@section('back', route('tma.home'))

@section('content')
@php
    $variants = $product->activeVariants->sortBy(fn ($v) => (float) $v->final_price)->values();
    $picked = $variants->first(fn ($v) => (float) $v->stock >= 1);
@endphp

<div class="gallery">
    @forelse ($product->images->sortByDesc('is_main') as $image)
        <div class="thumb"><img src="{{ $image->url }}" alt="{{ $product->name }}"></div>
    @empty
        <div class="thumb"><span aria-hidden="true">🛒</span></div>
    @endforelse
</div>

<p class="muted small">{{ $product->brand->name }}</p>
<h1>{{ $product->name }}</h1>
@if ($product->description)
    <p class="muted">{{ $product->description }}</p>
@endif

<form method="POST" action="{{ route('cart.items.store') }}" data-ajax>
    @csrf
    <h2>Choose a size</h2>
    @foreach ($variants as $variant)
        <label class="option">
            <input type="radio" name="variant_id" value="{{ $variant->id }}" required @checked($picked?->id === $variant->id) @disabled((float) $variant->stock < 1)>
            <span class="grow">
                {{ $variant->name }}
                @if ((float) $variant->stock < 1)
                    <span class="muted small">(sold out)</span>
                @endif
            </span>
            <span>
                <span class="price">${{ $variant->final_price }}</span>
                @if ($variant->has_discount)
                    <span class="old">${{ $variant->price }}</span>
                @endif
            </span>
        </label>
    @endforeach

    <label class="field">
        <span>Quantity</span>
        <input type="number" name="quantity" value="1" min="1" max="99" inputmode="numeric">
    </label>

    <button type="submit" class="btn btn-block" @disabled(! $picked)>{{ $picked ? 'Add to cart' : 'Sold out' }}</button>
</form>
@endsection
