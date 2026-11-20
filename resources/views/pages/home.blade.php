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
{{-- Demo data (keyingi bosqichda bazadan keladi). Mahsulotlar controllerdan: $featured, $discounted --}}
@php
    $categories = [
        ['name' => 'Fruits & Veges',    'icon' => 'icon-vegetables-broccoli.png',        'count' => 48],
        ['name' => 'Breads & Sweets',   'icon' => 'icon-bread-baguette.png',             'count' => 26],
        ['name' => 'Meat & Poultry',    'icon' => 'icon-animal-products-drumsticks.png', 'count' => 19],
        ['name' => 'Soft Drinks',       'icon' => 'icon-soft-drinks-bottle.png',         'count' => 31],
        ['name' => 'Herbs & Flour',     'icon' => 'icon-bread-herb-flour.png',           'count' => 22],
        ['name' => 'Wine & Spirits',    'icon' => 'icon-wine-glass-bottle.png',          'count' => 14],
    ];

    $banners = [
        ['img' => 'foodmart/images/ad-image-1.png', 'kicker' => 'Up to 20% off',  'title' => 'Fresh Fruits & Veggie Bags',  'glow' => '0,245,155'],
        ['img' => 'foodmart/images/ad-image-2.png', 'kicker' => 'Baked at dawn',   'title' => 'Artisan Breads & Baskets',    'glow' => '245,158,11'],
        ['img' => 'foodmart/images/ad-image-3.png', 'kicker' => 'Gift ready',      'title' => 'Premium Chocolate Boxes',     'glow' => '168,85,247'],
        ['img' => 'foodmart/images/ad-image-4.png', 'kicker' => 'New this week',   'title' => 'Handmade Party Cupcakes',     'glow' => '244,63,94'],
    ];

    $posts = [
        ['img' => 'foodmart/images/post-thumb-1.jpg', 'tag' => 'Seasonal',  'date' => '22 Aug 2025', 'title' => 'Top 10 casual fruit bowls for summer', 'excerpt' => 'Citrus, pomegranate and lime: how to build a vitamin-packed bowl in five minutes.'],
        ['img' => 'foodmart/images/post-thumb-2.jpg', 'tag' => 'Pantry',    'date' => '25 Aug 2025', 'title' => 'Nut butters worth stocking up on',     'excerpt' => 'Cashew, almond or peanut? Our buyers compare taste, texture and protein per spoon.'],
        ['img' => 'foodmart/images/post-thumb-3.jpg', 'tag' => 'Nutrition', 'date' => '28 Aug 2025', 'title' => 'Why broccoli deserves a weekly slot',  'excerpt' => 'Fibre, vitamin K and a surprisingly fast roast: simple ways to cook it better.'],
    ];
@endphp

    @include('partials.home-hero')
    @include('partials.home-trust')
    @include('partials.home-categories')

    @include('partials.home-products', [
        'eyebrow' => 'Direct from Farm', 'title' => 'Weekly Featured Harvest',
        'sub' => 'Hand-picked seasonal fruits and vegetables selected for peak vitamin density',
        'cta' => 'View all',
        'items' => $featured,
    ])

    @include('partials.home-banners')

    @include('partials.home-products', [
        'eyebrow' => 'On sale', 'title' => 'Discounted Picks',
        'sub' => 'Great prices on customer favourites',
        'cta' => 'Shop deals', 'items' => $discounted, 'cols' => 'lg:grid-cols-4',
    ])

    @include('partials.home-blog')
    @include('partials.home-app')
@endsection
