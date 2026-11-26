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
{{-- Bazadan: $featured, $discounted, $categories (HomeController). --}}

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
@endsection
