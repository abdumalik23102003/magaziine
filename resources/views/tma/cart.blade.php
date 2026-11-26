@extends('tma.layout')

@section('title', 'Cart')
@section('back', route('tma.home'))

@section('content')
<h1>Your cart</h1>

@if ($cart['items'] === [])
    <div class="center">
        <p>Your cart is empty.</p>
        <a href="{{ route('tma.home') }}" class="btn">Start shopping</a>
    </div>
@else
    @foreach ($cart['items'] as $item)
        <div class="line">
            <div class="thumb">
                @if ($item['img'])
                    <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}">
                @else
                    <span aria-hidden="true">🛒</span>
                @endif
            </div>
            <div class="grow">
                <a href="{{ route('tma.products.show', $slugs[$item['product_id']]) }}"><b>{{ $item['name'] }}</b></a>
                <div class="muted small">{{ $item['variant'] }} &middot; ${{ $item['price'] }}</div>
                <div class="stepper">
                    <form method="POST" action="{{ $item['qty'] > 1 ? route('cart.items.update', $item['id']) : route('cart.items.destroy', $item['id']) }}" data-once>
                        @csrf
                        @method($item['qty'] > 1 ? 'PATCH' : 'DELETE')
                        <input type="hidden" name="quantity" value="{{ $item['qty'] - 1 }}">
                        <button type="submit" class="btn btn-ghost btn-small" aria-label="Decrease {{ $item['name'] }}">&minus;</button>
                    </form>
                    <b>{{ $item['qty'] }}</b>
                    <form method="POST" action="{{ route('cart.items.update', $item['id']) }}" data-once>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="quantity" value="{{ $item['qty'] + 1 }}">
                        <button type="submit" class="btn btn-ghost btn-small" aria-label="Increase {{ $item['name'] }}" @disabled($item['qty'] >= $item['max'])>+</button>
                    </form>
                    <form method="POST" action="{{ route('cart.items.destroy', $item['id']) }}" data-once>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-small">Remove</button>
                    </form>
                </div>
            </div>
            <b>${{ $item['line_total'] }}</b>
        </div>
    @endforeach

    <div class="row"><span class="muted">Subtotal</span><span>${{ $cart['subtotal'] }}</span></div>
    @if ($cart['tax_rate'])
        <div class="row"><span class="muted">Tax</span><span>${{ $cart['tax'] }}</span></div>
    @endif
    <div class="row"><span class="muted">Delivery</span><span>{{ $cart['qualifies'] ? 'FREE' : 'at checkout' }}</span></div>
    @unless ($cart['qualifies'])
        <p class="muted small">Add ${{ $cart['remaining'] }} more for free delivery.</p>
    @endunless
    <div class="row total"><span>Total</span><span>${{ $cart['total'] }}</span></div>

    <a href="{{ route('tma.checkout') }}" class="btn btn-block">Checkout</a>
@endif
@endsection
