@extends('tma.layout')

@section('title', 'Checkout')
@section('back', route('tma.cart'))

@section('content')
@php
    $delivery = array_key_exists(old('delivery_method', ''), $methods) ? old('delivery_method') : array_key_first($methods);
    $payment = array_key_exists(old('payment_method', ''), $payments) ? old('payment_method') : array_key_first($payments);
@endphp

<h1>Checkout</h1>

<form method="POST" action="{{ route('tma.checkout.store') }}" data-once>
    @csrf

    <label class="field"><span>Full name</span>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="120" autocomplete="name" required>
    </label>
    <label class="field"><span>Phone</span>
        <input type="tel" name="phone" value="{{ old('phone', $phone) }}" minlength="7" maxlength="32" autocomplete="tel" placeholder="+998 90 123 45 67" required>
    </label>
    <label class="field"><span>City</span>
        <input type="text" name="city" value="{{ old('city') }}" maxlength="50" autocomplete="address-level2" required>
    </label>
    <label class="field"><span>Street address</span>
        <input type="text" name="address" value="{{ old('address', $user->address) }}" maxlength="150" autocomplete="address-line1" required>
    </label>
    <label class="field"><span>Apartment / entrance (optional)</span>
        <input type="text" name="apartment" value="{{ old('apartment') }}" maxlength="50" autocomplete="address-line2">
    </label>

    <input type="hidden" id="delivery_lat" name="delivery_lat" value="{{ old('delivery_lat') }}">
    <input type="hidden" id="delivery_lng" name="delivery_lng" value="{{ old('delivery_lng') }}">
    <button type="button" id="tma-locate" class="btn btn-ghost btn-small">📍 Add my location</button>
    <span id="tma-locate-info" class="muted small" role="status">{{ old('delivery_lat') ? 'Location added.' : '' }}</span>

    <h2>Delivery</h2>
    @foreach ($methods as $key => $method)
        <label class="option">
            <input type="radio" name="delivery_method" value="{{ $key }}" data-fee="{{ $method['fee_value'] }}" @checked($delivery === $key)>
            <span class="grow">{{ $method['name'] }}<br><span class="muted small">{{ $method['text'] }}</span></span>
            <b>{{ (float) $method['fee_value'] === 0.0 ? 'FREE' : '$'.$method['fee_value'] }}</b>
        </label>
    @endforeach

    <h2>Payment</h2>
    @foreach ($payments as $key => $item)
        <label class="option">
            <input type="radio" name="payment_method" value="{{ $key }}" @checked($payment === $key)>
            <span class="grow">{{ $item['name'] }}<br><span class="muted small">{{ $item['text'] }}</span></span>
        </label>
    @endforeach

    <label class="field"><span>Note for the courier (optional)</span>
        <textarea name="note" rows="2" maxlength="1000">{{ old('note') }}</textarea>
    </label>

    <div class="row total">
        <span>Total</span>
        <span id="tma-total" data-base="{{ $cart['total'] }}">${{ number_format((float) $cart['total'] + (float) $methods[$delivery]['fee_value'], 2) }}</span>
    </div>

    <button type="submit" class="btn btn-block">Place order</button>
    <p class="muted small">You pay on delivery. No card details are collected here.</p>
</form>
@endsection
