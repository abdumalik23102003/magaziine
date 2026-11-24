@extends('layouts.app')

@section('title', 'Checkout - FoodMart')

@push('scripts')
    <script src="{{ asset('js/pages/checkout.js') }}"></script>
@endpush

@section('content')
@php
    $pickedDelivery = array_key_exists(old('delivery_method', ''), $methods) ? old('delivery_method') : array_key_first($methods);
    $pickedPayment = array_key_exists(old('payment_method', ''), $payments) ? old('payment_method') : array_key_first($payments);
    $deliveryFee = $methods[$pickedDelivery]['fee_value'];
    $totalNow = number_format((float) $cart['total'] + (float) $deliveryFee, 2, '.', '');
    $radioCard = 'h-full rounded-2xl border border-border-glass bg-surface-carbon p-4 transition-all hover:border-primary/40 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:shadow-[0_0_20px_rgba(0,245,155,0.2)]';
@endphp

<x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Cart', 'url' => route('cart.show')], ['label' => 'Checkout', 'url' => null]]" />

<h1 class="mt-6 mb-8 font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">Checkout</h1>

<form id="checkout-form" method="POST" action="{{ route('checkout.store') }}"
      data-subtotal="{{ $cart['subtotal'] }}" data-tax="{{ $cart['tax'] }}" data-free-from="{{ $cart['free_from'] }}">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 space-y-6">

            <section class="dark-glass-card rounded-3xl p-6">
                <h2 class="font-display font-bold text-xl text-text-crisp mb-5">Delivery details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-field name="name" label="Full name" :value="$user->name" autocomplete="name" required />
                    <x-field name="phone" label="Phone" type="tel" :value="$user->phone" autocomplete="tel" placeholder="+998 90 123 45 67" required />
                    <x-field name="city" label="City" autocomplete="address-level2" required />
                    <x-field name="apartment" label="Apartment / entrance (optional)" autocomplete="address-line2" />
                    <x-field class="md:col-span-2" name="address" label="Street address" :value="$user->address" autocomplete="address-line1" required />
                </div>
            </section>

            <section class="dark-glass-card rounded-3xl p-6">
                <h2 class="font-display font-bold text-xl text-text-crisp mb-5">Delivery method</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ($methods as $key => $method)
                        <label class="cursor-pointer">
                            <input type="radio" name="delivery_method" value="{{ $key }}" data-fee="{{ $method['fee_value'] }}" class="peer sr-only" @checked($pickedDelivery === $key)>
                            <div class="{{ $radioCard }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">{{ $method['tag'] }}</span>
                                    <span class="font-mono text-xs font-bold text-text-crisp">{{ (float) $method['fee_value'] === 0.0 ? 'FREE' : '$'.$method['fee_value'] }}</span>
                                </div>
                                <p class="mt-2 font-display font-bold text-text-crisp">{{ $method['name'] }}</p>
                                <p class="mt-1 text-xs text-text-muted leading-relaxed">{{ $method['text'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('delivery_method') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </section>

            <section class="dark-glass-card rounded-3xl p-6">
                <h2 class="font-display font-bold text-xl text-text-crisp mb-5">Payment</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($payments as $key => $payment)
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="{{ $key }}" class="peer sr-only" @checked($pickedPayment === $key)>
                            <div class="{{ $radioCard }} flex items-start gap-3">
                                <i class="bi {{ $payment['icon'] }} text-2xl text-primary"></i>
                                <div>
                                    <p class="font-display font-bold text-text-crisp">{{ $payment['name'] }}</p>
                                    <p class="mt-1 text-xs text-text-muted leading-relaxed">{{ $payment['text'] }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('payment_method') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </section>

            <section class="dark-glass-card rounded-3xl p-6">
                <label for="note" class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">Note for the courier (optional)</label>
                <textarea id="note" name="note" rows="3" maxlength="1000" class="mt-2 w-full resize-none rounded-xl bg-surface-carbon border border-border-glass px-4 py-3 text-sm text-text-crisp placeholder:text-zinc-500 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Leave at the door, call before arrival...">{{ old('note') }}</textarea>
                @error('note') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </section>
        </div>

        <aside class="lg:col-span-4 lg:sticky lg:top-40 dark-glass-card rounded-3xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-display font-bold text-xl text-text-crisp">Order summary</h2>
                <a href="{{ route('cart.show') }}" class="text-[11px] font-mono uppercase tracking-wider text-primary hover:underline">Edit</a>
            </div>

            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                @foreach ($cart['items'] as $item)
                    <div class="flex items-center gap-3">
                        <div class="relative w-14 h-14 shrink-0 rounded-xl bg-surface-obsidian border border-border-glass overflow-hidden flex items-center justify-center">
                            @if ($item['img'])
                                <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-contain p-1.5">
                            @else
                                <span class="text-xl">🛒</span>
                            @endif
                            <span class="absolute bottom-0 right-0 bg-black/80 px-1 text-[10px] font-mono font-bold text-text-crisp">x{{ $item['qty'] }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-text-crisp">{{ $item['name'] }}</p>
                            <p class="text-[11px] font-mono text-text-muted">{{ $item['variant'] }}</p>
                        </div>
                        <span class="font-mono text-sm font-bold text-text-crisp">${{ $item['line_total'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 space-y-2 border-t border-border-glass pt-4 text-sm font-mono">
                <div class="flex justify-between text-text-muted"><span>Subtotal</span><span class="text-text-crisp">${{ $cart['subtotal'] }}</span></div>
                @if ($cart['tax_rate'])
                    <div class="flex justify-between text-text-muted"><span>Tax</span><span class="text-text-crisp">${{ $cart['tax'] }}</span></div>
                @endif
                <div class="flex justify-between text-text-muted"><span>Delivery</span><span class="text-primary font-bold" id="sum-delivery">{{ (float) $deliveryFee === 0.0 ? 'FREE' : '$'.$deliveryFee }}</span></div>
                <div class="flex items-baseline justify-between border-t border-border-glass pt-3 font-display text-text-crisp">
                    <span class="font-bold">Total</span>
                    <span class="text-2xl font-black text-primary" id="sum-total">${{ $totalNow }}</span>
                </div>
            </div>

            <button type="submit" id="place-order-btn" class="water-ripple-target neon-glow-btn mt-6 w-full rounded-full bg-primary hover:bg-emerald-400 py-4 font-display text-xs sm:text-sm font-bold uppercase tracking-wider text-black flex items-center justify-center gap-2 transition-all">
                <i class="bi bi-shield-lock-fill"></i><span>Place order</span>
            </button>
            <p class="mt-3 text-center text-[11px] text-text-muted">You pay on delivery. No card details are collected here.</p>
        </aside>
    </div>
</form>
@endsection
