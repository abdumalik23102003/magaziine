@extends('layouts.app')

@section('title', 'Brands - FoodMart')

@section('content')
<x-breadcrumbs :items="$breadcrumbs" />

<header class="mt-6 mb-8">
    <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-text-crisp">Our brands</h1>
    <p class="mt-1 text-sm text-text-muted">{{ $brands->total() }} {{ $brands->total() === 1 ? 'brand' : 'brands' }}</p>
</header>

@if ($brands->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach ($brands as $brand)
            <x-brand-card :brand="$brand" class="w-full" />
        @endforeach
    </div>

    {{ $brands->links() }}
@else
    <div class="dark-glass-card rounded-3xl p-12 text-center">
        <p class="font-display font-bold text-xl text-text-crisp">No brands yet</p>
        <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-2.5 text-xs font-display font-bold uppercase tracking-wider text-black hover:bg-emerald-400 transition-colors">Browse the catalog</a>
    </div>
@endif
@endsection
