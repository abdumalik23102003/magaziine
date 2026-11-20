<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'FoodMart Biological Groceries')</title>

    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,300..900;1,300..900&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/tailwind-config-neon.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/neon-shell.css') }}">
    @stack('styles')
</head>
<body class="bg-surface-obsidian font-body-md text-body-md text-on-surface antialiased selection:bg-primary-container selection:text-on-primary-container">

    @include('partials.store-header')

    <main class="w-full pt-20 bg-surface-obsidian min-h-screen">
        @yield('content')
    </main>

    @include('partials.store-footer')

    @stack('scripts')
</body>
</html>
