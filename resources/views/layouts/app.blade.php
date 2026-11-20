<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'FoodMart - High Performance Organic Market')</title>

    {{-- Tailwind CSS (CDN) & icons --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700;800&display=swap" rel="stylesheet"/>
    <script src="{{ asset('js/tailwind-config.js') }}"></script>

    {{-- Global styles (used by EVERY page) --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- Page-specific styles: @push('styles') ... @endpush --}}
    @stack('styles')
</head>
<body class="selection:bg-primary selection:text-black">

    {{-- Ambient kinetic atmosphere glows --}}
    <div class="kinetic-ambient-1"></div>
    <div class="kinetic-ambient-2"></div>
    <div class="kinetic-ambient-3"></div>

    {{-- Water droplet / fluid ion cursor canvas --}}
    <canvas id="water-cursor-canvas"></canvas>

    <div class="relative z-10 flex flex-col min-h-screen">
        @include('partials.announcement-bar')
        @include('partials.header')

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    {{-- Liked-products slide-in drawer (opened from the header heart button) --}}
    @include('partials.wishlist-drawer')

    {{-- Shopping-cart slide-in drawer (opened from the header bag button) --}}
    @include('partials.cart-drawer')

    {{-- Global scripts (used by EVERY page) --}}
    <script src="{{ asset('js/cart-drawer.js') }}"></script>
    <script src="{{ asset('js/cart.js') }}"></script>
    <script src="{{ asset('js/ripple.js') }}"></script>
    <script src="{{ asset('js/cursor.js') }}"></script>
    <script src="{{ asset('js/wishlist-drawer.js') }}"></script>

    {{-- Page-specific scripts: @push('scripts') ... @endpush --}}
    @stack('scripts')
</body>
</html>
