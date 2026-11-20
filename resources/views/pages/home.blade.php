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
{{-- Demo data. Later this all comes from a controller / database.
     Every 'img' is a file name inside public/foodmart/images (your original template images). --}}
@php
    $categories = [
        ['name' => 'Fruits & Veges',    'icon' => 'icon-vegetables-broccoli.png',        'count' => 48],
        ['name' => 'Breads & Sweets',   'icon' => 'icon-bread-baguette.png',             'count' => 26],
        ['name' => 'Meat & Poultry',    'icon' => 'icon-animal-products-drumsticks.png', 'count' => 19],
        ['name' => 'Soft Drinks',       'icon' => 'icon-soft-drinks-bottle.png',         'count' => 31],
        ['name' => 'Herbs & Flour',     'icon' => 'icon-bread-herb-flour.png',           'count' => 22],
        ['name' => 'Wine & Spirits',    'icon' => 'icon-wine-glass-bottle.png',          'count' => 14],
    ];

    $featured = [
        ['id' => 'prod-1', 'img' => 'foodmart/images/thumb-avocado.png',    'glow' => '0,245,155',  'category' => 'Organic Produce',  'title' => 'Hass Creamy Avocados (Pack of 4)',  'rating' => 4.5, 'reviews' => 124, 'price' => '4.49', 'oldPrice' => '5.99', 'badge' => '-25%'],
        ['id' => 'prod-2', 'img' => 'foodmart/images/thumb-raspberries.png','glow' => '244,63,94',  'category' => 'Berries & Fruits', 'title' => 'Sweet Alpine Raspberries (6 oz)',   'rating' => 5,   'reviews' => 89,  'price' => '3.79', 'oldPrice' => '4.50', 'badge' => '-15%'],
        ['id' => 'prod-3', 'img' => 'foodmart/images/thumb-cucumber.png',   'glow' => '0,245,155',  'category' => 'Fresh Greens',     'title' => 'Crisp Farm Cucumbers',              'rating' => 4,   'reviews' => 42,  'price' => '2.29'],
        ['id' => 'prod-4', 'img' => 'foodmart/images/thumb-tomatoes.png',   'glow' => '249,115,22', 'category' => 'Vine Harvest',     'title' => 'Vine-Ripened Cherry Tomatoes',      'rating' => 4.5, 'reviews' => 96,  'price' => '5.19', 'oldPrice' => '7.49', 'badge' => '-30%'],
        ['id' => 'prod-5', 'img' => 'foodmart/images/thumb-bananas.png',    'glow' => '245,158,11', 'category' => 'Tropical Fruits',  'title' => 'Organic Fairtrade Bananas',         'rating' => 4.5, 'reviews' => 77,  'price' => '1.99'],
        ['id' => 'prod-6', 'img' => 'foodmart/images/thumb-milk.png',       'glow' => '56,189,248', 'category' => 'Dairy & Eggs',     'title' => 'Farm Fresh Whole Milk (2 pack)',    'rating' => 4,   'reviews' => 58,  'price' => '3.49', 'oldPrice' => '4.20', 'badge' => '-17%'],
        ['id' => 'prod-7', 'img' => 'foodmart/images/thumb-orange-juice.png','glow' => '249,115,22','category' => 'Beverages',        'title' => 'Cold-Pressed Orange Juice',         'rating' => 5,   'reviews' => 133, 'price' => '4.25'],
        ['id' => 'prod-8', 'img' => 'foodmart/images/thumb-biscuits.png',   'glow' => '245,158,11', 'category' => 'Bakery',           'title' => 'Crunchy Butter Biscuits',           'rating' => 4,   'reviews' => 35,  'price' => '2.79', 'oldPrice' => '3.30', 'badge' => '-15%'],
    ];

    $pantry = [
        ['id' => 'prod-9',  'img' => 'foodmart/images/product-thumb-1.png',  'glow' => '245,158,11', 'category' => 'Smoothies',    'title' => 'Smooth Fruit Juice Bottle',       'rating' => 4.5, 'reviews' => 64, 'price' => '3.99'],
        ['id' => 'prod-10', 'img' => 'foodmart/images/product-thumb-2.png',  'glow' => '244,63,94',  'category' => 'Sauces',       'title' => 'Classic Tomato Ketchup',          'rating' => 4,   'reviews' => 81, 'price' => '2.49', 'oldPrice' => '2.99', 'badge' => '-17%'],
        ['id' => 'prod-11', 'img' => 'foodmart/images/product-thumb-11.jpg', 'glow' => '245,158,11', 'category' => 'Pantry',       'title' => 'Wildflower Raw Honey Jar',        'rating' => 5,   'reviews' => 102,'price' => '8.90'],
        ['id' => 'prod-12', 'img' => 'foodmart/images/product-thumb-12.jpg', 'glow' => '0,245,155',  'category' => 'Herbs & Tea',  'title' => 'Dried Herb Blend (Resealable)',   'rating' => 4.5, 'reviews' => 47, 'price' => '5.60'],
        ['id' => 'prod-13', 'img' => 'foodmart/images/product-thumb-13.jpg', 'glow' => '0,245,155',  'category' => 'Seafood',      'title' => 'Wild Caught Tuna Tin',            'rating' => 4,   'reviews' => 29, 'price' => '4.15', 'oldPrice' => '4.90', 'badge' => '-15%'],
        ['id' => 'prod-14', 'img' => 'foodmart/images/product-thumb-14.jpg', 'glow' => '244,63,94',  'category' => 'Snacks',       'title' => 'Hearty Food Pouch Meal',          'rating' => 4.5, 'reviews' => 53, 'price' => '6.30'],
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
        'cta' => 'View All 48 Items',
        'items' => $products,
//        'items' => $featured,
    ])

    @include('partials.home-banners')

    @include('partials.home-products', [
        'eyebrow' => 'Customer favourites', 'title' => 'Pantry Best Sellers',
        'sub' => 'Staples that keep coming back to the basket',
        'cta' => 'Shop Pantry', 'items' => $products, 'cols' => 'lg:grid-cols-3',
    ])

    @include('partials.home-blog')
    @include('partials.home-app')
@endsection
