@extends('layouts.checkout')

@section('title', 'FoodMart - Checkout')

@push('scripts')
    <script src="{{ asset('js/pages/checkout.js') }}"></script>
@endpush

@section('content')
@php
    // Temporary demo data - later pass these from a controller (cart, user, shipping).
    $orderItems = [
        ['name' => 'Hass Creamy Avocados', 'meta' => 'Pack of 4 \u2022 Ventura County', 'price' => 4.49, 'discount' => null, 'qty' => 1,
         'alt' => 'High quality studio shot of four ripe dark green Haas avocados cut in half showing creamy vibrant lime-green interior and dark pit on black obsidian slate reflective table, professional food commercial lighting, sharp crisp details',
         'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBQTb_bXIscGBRO0uBJd6u5G29qc9hgQmQsuu1UDhoDvLakoez2Y5wwOCJkrgEE-iA64_g3ycNpU7xEg0NJFTx1eJP73Ng_MsKjb_dKU_GMGimf6ygXfkJwixlNumyRN615XsvQflRfOd7hwm3uh0oT_aFcBcaid_ByLnY9ILE8RsmJGP1xoORi2Yh0l3N4DHL82OH1lHjs0PxJg0HXm68SCI6uQy_1zQR1pweRaw4'],
        ['name' => 'Alpine Strawberries', 'meta' => '1 lb \u2022 Santa Cruz Mountain Bio', 'price' => 3.79, 'discount' => 15, 'qty' => 1,
         'alt' => 'Luscious organic deep ruby red alpine strawberries resting inside modern black thermal carton, micro condensation water droplets, dark background, rich organic textures with vibrant green stems',
         'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDeNgfY3yisOz3mJNYKn7DKKEmL0I7FFqnns_r0Wa_brZQ_mDwZN8-1bKWMVUpiFc19USo4SZfbWJ3w6DohyeMsc__upyDOAC5aFxiqhyubE5qIBah6nDZs6FrAKgvz3p0WV67oIeNx2RVyGtZG6iBm9vAZ0uclYbBRuSSQ-VWYnl_EJR066uUDaGASVy_66osr3v4RhvqzEt0SMBNojvLaUH8lBfaswdfP71P2aCU'],
        ['name' => 'Crisp Broccoli Crowns', 'meta' => '1 Head \u2022 Salinas Hydro Node', 'price' => 2.29, 'discount' => null, 'qty' => 1,
         'alt' => 'Ultra crisp fresh farm broccoli crowns with dense dark emerald florets, studio macro lighting, isolated against matte black void backdrop, hyper fresh clean aesthetic',
         'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCY_LJDy2HDpyLNHWL05ZTXdG0bSAqHrG85UHQf1CcMtlzCfsK8PAK4ypaFdeM8PD-ryEAfcUeQOtPypfnjKxGh7dsE6_cwXA5YKQv7qkVdb-WECRXGV375bnXKoXN6O5mDhTipkyqZf3F1f9l93dmc1YpDWkr-XeOttroBTg8G_dafbHOxGH8V1wkbLfXa0YA_IWlO0Ck876zmOCx6ZouIhVPcAVJy66DPNzEQa5I'],
        ['name' => 'Valencia Oranges', 'meta' => '3 lb Bag \u2022 Ojai Heritage Grove', 'price' => 5.19, 'discount' => 30, 'qty' => 1,
         'alt' => 'Sun-ripened deep orange Valencia citrus fruits displayed in structured dark minimalist basket, glistening fresh texture with dewy skin against obsidian background',
         'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB8_PpSPx9Sm3DSt8y1OEEyKcofD30FSHz_St7t4sskSD32btTsphh_f_QBspsYcG1-cgik8O-Au9pfGje09oZfaZ9qGiF4yuTqSCEVeMitBTMt1bgBDffO0SXwTFZdYLM4VCO49y118uMIAOxWA_D3Bja8k-sHecrJ1KQOZzUhr4r6_ayb50Jwgom93teIcaw_a9l-lALidZeHYB3kdFFaj9eEgkTOageFyIeTXUg'],
        ['name' => 'Artisan Sourdough Boule', 'meta' => '72-hr Ferment \u2022 Tartine Strain', 'price' => 6.49, 'discount' => null, 'qty' => 1,
         'alt' => 'Artisanal rustic dark-crusted sourdough bread boule scored with botanical pattern, dusted with heirloom white flour, sliced to show wild open crumb structure, high contrast moody dark studio photography',
         'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD2xFyC3m3DNdkGVVzSpKXUGzjzlUQi9YhlR9uApGIlLDVgHSZmtvxlLVk4GVa61CFACjyJxZlcAzQ9oDoLm8OBbnQ6RVZGfqWsE4B28UDApool0yFsNooIFKJZvnaLKO6_oXMG-GicE1h0XP7ZBVf7WxRNNOQ8DsST68iebmd3ZbdTk9glXVE-DdVx5WjtrktzmNywBPYEeDFS0-KsCblekgHcth6ZiVJf_guMkIs'],
    ];

    $subtotal  = collect($orderItems)->sum(fn ($i) => $i['price'] * $i['qty']);
    $voucher   = 2.00;
    $tax       = 1.25;
    $roundup   = 0.75;
    $total     = $subtotal - $voucher + $tax;
@endphp

<div class="flex flex-col w-full">
<div class="relative w-full overflow-hidden"
     id="checkout-root"
     data-subtotal="{{ number_format($subtotal, 2, '.', '') }}"
     data-voucher="{{ number_format($voucher, 2, '.', '') }}"
     data-tax="{{ number_format($tax, 2, '.', '') }}"
     data-roundup="{{ number_format($roundup, 2, '.', '') }}">

    {{-- Ambient glows --}}
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary-container/10 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute top-1/3 -right-40 w-[500px] h-[500px] bg-secondary-fixed/5 rounded-full blur-[160px] pointer-events-none"></div>

    <div class="w-full max-w-[1560px] mx-auto px-margin-mobile md:px-gutter xl:px-margin py-space-lg">

        {{-- Stepper header --}}
        <section class="w-full mb-space-xl">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-md mb-space-lg">
                <div>
                    <div class="flex items-center gap-2 mb-space-xs">
                        <span class="inline-flex items-center justify-center w-2 h-2 rounded-full bg-primary-container animate-ping"></span>
                        <span class="font-label-sm text-label-sm uppercase tracking-[0.2em] text-primary-container font-semibold">Active Checkout Session #FM-88290</span>
                    </div>
                    <h1 class="font-headline-lg text-headline-lg text-text-crisp tracking-tight uppercase flex items-center gap-space-xs">
                        Direct Intake Dispatch
                        <span class="text-text-muted font-body-sm text-body-sm font-normal normal-case tracking-normal px-2 py-0.5 rounded bg-surface-slate">Node 04 • Bio-Direct</span>
                    </h1>
                </div>
                <div class="flex items-center gap-space-xs text-text-muted font-label-md text-label-md uppercase tracking-wider bg-surface-slate/80 px-space-md py-2 rounded-full">
                    <span class="material-symbols-outlined text-primary-container text-[18px]">lock_clock</span>
                    <span>Cart Reserved for <span class="text-accent-electric-lime font-bold" id="cart-timer">14:59</span></span>
                </div>
            </div>

            @include('partials.checkout-stepper')
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter items-start">

            {{-- LEFT: forms --}}
            <div class="lg:col-span-8 flex flex-col gap-space-xl">
                @include('partials.checkout-express')
                @include('partials.checkout-delivery')
                @include('partials.checkout-payment')
            </div>

            {{-- RIGHT: sticky order summary --}}
            @include('partials.checkout-summary')
        </div>
    </div>
</div>
</div>
@endsection
