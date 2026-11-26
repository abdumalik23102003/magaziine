@extends('layouts.app')

@section('title', 'Order '.$order->number.' - FoodMart')

@section('content')
@php
    $methods = config('shop.delivery_methods');
    $payments = config('shop.payment_methods');
@endphp

<x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Profile', 'url' => route('profile')], ['label' => $order->number, 'url' => null]]" />

<header class="mt-6 mb-8 flex flex-wrap items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">Order {{ $order->number }}</h1>
        <span class="px-3 py-1 rounded-full border text-xs font-mono font-bold uppercase tracking-wider {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
    </div>
    <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('orders.reorder', $order) }}">
            @csrf
            <button type="submit" class="rounded-full bg-primary/10 border border-primary/30 px-5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-primary hover:bg-primary hover:text-black transition-colors">Reorder</button>
        </form>
        @if ($order->isCancellableByCustomer())
            <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                @csrf
                <button type="submit" class="rounded-full border border-red-400/40 px-5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-red-300 hover:bg-red-500/10 transition-colors">Cancel order</button>
            </form>
        @endif
    </div>
</header>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <section class="lg:col-span-8 dark-glass-card rounded-3xl p-6">
        <h2 class="font-display font-bold text-xl text-text-crisp mb-4">Items</h2>
        <div class="divide-y divide-border-glass">
            @foreach ($order->items as $item)
                <div class="flex items-center justify-between gap-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-text-crisp">{{ $item->product_name }}</p>
                        <p class="text-xs font-mono text-text-muted">{{ $item->variant_name }} &middot; ${{ $item->price }} x {{ (int) $item->quantity }}</p>
                    </div>
                    <span class="font-mono font-bold text-text-crisp">${{ number_format((float) $item->price * (float) $item->quantity, 2, '.', '') }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-4 space-y-2 border-t border-border-glass pt-4 text-sm font-mono">
            <div class="flex justify-between text-text-muted"><span>Subtotal</span><span class="text-text-crisp">${{ $order->subtotal }}</span></div>
            @if ((float) $order->tax > 0)
                <div class="flex justify-between text-text-muted"><span>Tax</span><span class="text-text-crisp">${{ $order->tax }}</span></div>
            @endif
            <div class="flex justify-between text-text-muted"><span>Delivery</span><span class="text-text-crisp">{{ (float) $order->delivery_fee === 0.0 ? 'FREE' : '$'.$order->delivery_fee }}</span></div>
            <div class="flex justify-between border-t border-border-glass pt-3 font-display text-lg font-black text-text-crisp"><span>Total</span><span class="text-primary">${{ $order->total_price }}</span></div>
        </div>
    </section>

    <aside class="lg:col-span-4 dark-glass-card rounded-3xl p-6 space-y-4 text-sm">
        <div>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">Placed</p>
            <p class="text-text-crisp">{{ $order->created_at->format('d M Y, H:i') }}</p>
        </div>
        <div>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">Recipient</p>
            <p class="text-text-crisp">{{ $order->customer_name }}</p>
            <p class="text-text-muted">{{ $order->phone }}</p>
        </div>
        <div>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">Address</p>
            <p class="text-text-crisp">{{ $order->address }}</p>
            @if ($order->map_url)
                <a href="{{ $order->map_url }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1.5 text-xs font-mono text-primary hover:underline"><i class="bi bi-geo-alt"></i> View on map</a>
            @endif
        </div>
        <div>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">Delivery</p>
            <p class="text-text-crisp">{{ $methods[$order->delivery_method]['name'] ?? $order->delivery_method }}</p>
        </div>
        <div>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">Payment</p>
            <p class="text-text-crisp">{{ $payments[$order->payment_method]['name'] ?? $order->payment_method }}</p>
        </div>
        @if ($order->note)
            <div>
                <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">Note</p>
                <p class="text-text-muted">{{ $order->note }}</p>
            </div>
        @endif
    </aside>
</div>
@endsection
