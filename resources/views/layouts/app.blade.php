<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FoodMart - High Performance Organic Market')</title>

    {{-- Tailwind CSS (CDN) & icons --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@300..800&family=Hanken+Grotesk:wght@300..800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="{{ asset('js/tailwind-config.js') }}"></script>
    <script src="{{ asset('js/tailwind-config-extra.js') }}"></script>

    {{-- Global styles (used by EVERY page) --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- Page-specific styles: @push('styles') ... @endpush --}}
    @stack('styles')
</head>
<body class="selection:bg-primary selection:text-black">

    <div class="kinetic-ambient-1"></div>
    <div class="kinetic-ambient-2"></div>
    <div class="kinetic-ambient-3"></div>

    <canvas id="water-cursor-canvas"></canvas>

    <div class="relative z-10 flex flex-col min-h-screen">
        @include('partials.announcement-bar')
        @include('partials.header')

        @yield('before_main')

        {{-- Sahifa kengligi: product sahifasi kabi to'liq enli sahifalar @section('main_class', ...) bilan almashtiradi --}}
        <main class="@yield('main_class', 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1')">
            @include('partials.flash')
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    @include('partials.wishlist-drawer')
    @include('partials.cart-drawer')

    {{-- Server holati JS uchun (Js::from tag va qo'shtirnoqlarni xavfsiz qochiradi) --}}
    <script>
        window.FoodMart = {
            auth: {{ auth()->check() ? 'true' : 'false' }},
            urls: {
                cart: {{ Illuminate\Support\Js::from(url('/cart')) }},
                wishlist: {{ Illuminate\Support\Js::from(url('/wishlist')) }},
                checkout: {{ Illuminate\Support\Js::from(route('checkout')) }},
                login: {{ Illuminate\Support\Js::from(route('login')) }}
            },
            cart: {{ Illuminate\Support\Js::from($cartData) }},
            wishlist: {{ Illuminate\Support\Js::from($wishlistData) }}
        };
    </script>

    <script src="{{ asset('js/core.js') }}"></script>
    <script src="{{ asset('js/cart-drawer.js') }}"></script>
    <script src="{{ asset('js/wishlist-drawer.js') }}"></script>
    <script src="{{ asset('js/cart.js') }}"></script>
    <script src="{{ asset('js/ripple.js') }}"></script>
    <script src="{{ asset('js/cursor.js') }}"></script>
    <script src="{{ asset('js/header-actions.js') }}"></script>

    @stack('scripts')
    @include('partials.support-chat')
</body>
</html>
