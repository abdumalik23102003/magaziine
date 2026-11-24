@extends('layouts.app')

@section('title', 'Your cart - FoodMart')

@section('content')
<x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Cart', 'url' => null]]" />

<h1 class="mt-6 mb-8 font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">Your cart</h1>

@if ($cart['items'] === [])
    <div class="dark-glass-card rounded-3xl p-12 text-center">
        <p class="font-display font-bold text-xl text-text-crisp">Your cart is empty</p>
        <p class="mt-2 text-sm text-text-muted">Browse the catalog and add something fresh.</p>
        <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black hover:bg-emerald-400 transition-colors">Show all products</a>
    </div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <section class="lg:col-span-8 space-y-4">
            @foreach ($cart['items'] as $item)
                <div class="dark-glass-card rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                    <a href="{{ $item['url'] }}" class="w-20 h-20 shrink-0 rounded-xl bg-surface-obsidian border border-border-glass overflow-hidden flex items-center justify-center">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-contain p-2">
                        @else
                            <span class="text-3xl">🛒</span>
                        @endif
                    </a>
                    <div class="flex-1 min-w-0">
                        <a href="{{ $item['url'] }}" class="font-display font-bold text-text-crisp hover:text-primary transition-colors">{{ $item['name'] }}</a>
                        <p class="text-xs font-mono text-text-muted mt-0.5">{{ $item['variant'] }} &middot; ${{ $item['price'] }}@if ($item['old_price']) <span class="line-through text-zinc-500">${{ $item['old_price'] }}</span>@endif</p>
                    </div>

                    <form method="POST" action="{{ route('cart.items.update', $item['id']) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="number" name="quantity" value="{{ $item['qty'] }}" min="1" max="{{ $item['max'] }}" class="w-20 rounded-xl bg-surface-carbon border border-border-glass px-3 py-2 text-sm text-text-crisp text-center focus:border-primary focus:outline-none" aria-label="Quantity">
                        <button type="submit" class="rounded-full border border-border-glass px-3 py-2 text-[11px] font-mono font-bold uppercase tracking-wider text-text-muted hover:text-primary hover:border-primary/40 transition-colors">Update</button>
                    </form>

                    <div class="w-24 text-right font-display font-black text-text-crisp">${{ $item['line_total'] }}</div>

                    <form method="POST" action="{{ route('cart.items.destroy', $item['id']) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-8 h-8 rounded-lg text-zinc-500 hover:text-red-400 hover:bg-red-500/10 transition-colors" aria-label="Remove {{ $item['name'] }}"><i class="bi bi-x-lg text-xs"></i></button>
                    </form>
                </div>
            @endforeach

            <form method="POST" action="{{ route('cart.clear') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-mono text-text-muted hover:text-red-400 transition-colors">Clear cart</button>
            </form>
        </section>

        <aside class="lg:col-span-4 dark-glass-card rounded-2xl p-6 space-y-3 text-sm font-mono">
            <div class="flex justify-between text-text-muted"><span>Subtotal</span><span class="text-text-crisp font-bold">${{ $cart['subtotal'] }}</span></div>
            @if ($cart['tax_rate'])
                <div class="flex justify-between text-text-muted"><span>Tax</span><span class="text-text-crisp font-bold">${{ $cart['tax'] }}</span></div>
            @endif
            <div class="flex justify-between text-text-muted"><span>Delivery</span><span class="text-primary font-bold">{{ $cart['qualifies'] ? 'FREE' : 'at checkout' }}</span></div>
            @unless ($cart['qualifies'])
                <p class="text-[11px] text-accent-lime">Add ${{ $cart['remaining'] }} more for free delivery</p>
            @endunless
            <div class="flex justify-between border-t border-border-glass pt-3 font-display text-lg font-black text-text-crisp"><span>Total</span><span class="text-primary">${{ $cart['total'] }}</span></div>
            <a href="{{ route('checkout') }}" class="water-ripple-target block rounded-full bg-primary hover:bg-emerald-400 py-3 text-center font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Proceed to checkout</a>
            <a href="{{ route('catalog.index') }}" class="block text-center text-xs text-text-muted hover:text-primary transition-colors">Continue shopping</a>
        </aside>
    </div>
@endif
@endsection
