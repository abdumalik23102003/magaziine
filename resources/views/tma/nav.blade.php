@inject('tmaCart', 'App\Services\CartService')
@php($tmaCount = $tmaCart->summary()['count'])
<nav class="tabbar" aria-label="Shop sections">
    <a href="{{ route('tma.home') }}" class="{{ request()->routeIs('tma.home', 'tma.products.show') ? 'on' : '' }}"><b aria-hidden="true">🛍</b>Shop</a>
    <a href="{{ route('tma.cart') }}" class="{{ request()->routeIs('tma.cart', 'tma.checkout') ? 'on' : '' }}">
        <b aria-hidden="true">🛒</b>Cart
        <i class="count" id="tma-cart-count" @if (! $tmaCount) hidden @endif>{{ $tmaCount }}</i>
    </a>
    <a href="{{ route('tma.orders.index') }}" class="{{ request()->routeIs('tma.orders.*') ? 'on' : '' }}"><b aria-hidden="true">📦</b>Orders</a>
</nav>
