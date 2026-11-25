@extends('layouts.app')

@section('title', 'FoodMart - High Performance Organic Market')

{{-- Styles & script that ONLY the home page needs (hero physics sphere) --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/pages/home-physics.js') }}"></script>
@endpush

@section('content')
{{-- Bazadan: $featured, $discounted, $categories (HomeController). Banner massivi hali demo. --}}
@php
    $banners = [
        ['img' => 'foodmart/images/ad-image-1.png', 'kicker' => 'Up to 20% off',  'title' => 'Fresh Fruits & Veggie Bags',  'glow' => '0,245,155'],
        ['img' => 'foodmart/images/ad-image-2.png', 'kicker' => 'Baked at dawn',   'title' => 'Artisan Breads & Baskets',    'glow' => '245,158,11'],
        ['img' => 'foodmart/images/ad-image-3.png', 'kicker' => 'Gift ready',      'title' => 'Premium Chocolate Boxes',     'glow' => '168,85,247'],
        ['img' => 'foodmart/images/ad-image-4.png', 'kicker' => 'New this week',   'title' => 'Handmade Party Cupcakes',     'glow' => '244,63,94'],
    ];
@endphp

    @include('partials.home-hero')
    @include('partials.home-trust')
    @include('partials.home-categories')

    @if ($featured->isNotEmpty())
        @include('partials.home-products', [
            'eyebrow' => 'Direct from Farm', 'title' => 'Weekly Featured Harvest',
            'sub' => 'Hand-picked seasonal fruits and vegetables selected for peak vitamin density',
            'cta' => 'View all', 'href' => route('catalog.index'),
            'items' => $featured,
        ])
    @endif

    <x-brand-carousel :brands="$brandCarousel" />

    @if ($discounted->isNotEmpty())
        @include('partials.home-products', [
            'eyebrow' => 'On sale', 'title' => 'Discounted Picks',
            'sub' => 'Great prices on customer favourites',
            'cta' => 'Shop deals', 'href' => route('catalog.index', ['discounted' => 1]),
            'items' => $discounted,
        ])
    @endif

    @include('partials.home-blog')
    @include('partials.home-app')
@endsection
