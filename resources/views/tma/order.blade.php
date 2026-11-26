@extends('tma.layout')

@section('title', $order->number)
@section('back', route('tma.orders.index'))

@section('content')
<h1>Order {{ $order->number }}</h1>
<p>
    <span class="status status-{{ $order->status->value }}">{{ $order->status->label() }}</span>
    <span class="muted small">{{ $order->created_at->format('d M Y, H:i') }}</span>
</p>

@foreach ($order->items as $item)
    <div class="row">
        <span>{{ $item->product_name }}<br><span class="muted small">{{ $item->variant_name }} &middot; ${{ $item->price }} x {{ (int) $item->quantity }}</span></span>
        <b>${{ number_format((float) $item->price * (float) $item->quantity, 2) }}</b>
    </div>
@endforeach

<div class="row sep"><span class="muted">Subtotal</span><span>${{ $order->subtotal }}</span></div>
@if ((float) $order->tax > 0)
    <div class="row"><span class="muted">Tax</span><span>${{ $order->tax }}</span></div>
@endif
<div class="row"><span class="muted">Delivery</span><span>{{ (float) $order->delivery_fee === 0.0 ? 'FREE' : '$'.$order->delivery_fee }}</span></div>
<div class="row total"><span>Total</span><span>${{ $order->total_price }}</span></div>

<h2>Delivery to</h2>
<p>{{ $order->customer_name }}, {{ $order->phone }}<br><span class="muted">{{ $order->address }}</span></p>
<p class="muted small">
    {{ config('shop.delivery_methods')[$order->delivery_method]['name'] ?? $order->delivery_method }} &middot;
    {{ config('shop.payment_methods')[$order->payment_method]['name'] ?? $order->payment_method }}
</p>

@if ($order->isCancellableByCustomer())
    <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Cancel this order?">
        @csrf
        <button type="submit" class="btn btn-danger btn-block">Cancel order</button>
    </form>
@endif
@endsection
