@extends('layouts.app')

@section('title', 'FoodMart - '.$product->name)
@section('main_class', 'w-full flex-1')

@push('scripts')
    <script src="{{ asset('js/pages/product.js') }}"></script>
@endpush

@section('content')
@php
    // Bazadan (ProductController): $product, $related, $breadcrumbs.
    $variants = $product->activeVariants->values();

    // Asosiy rasm birinchi, qolganlari sort_order bo'yicha.
    $images = $product->images->sortByDesc('is_main')->values()->map(fn ($img, $i) => [
        'src' => $img->url,
        'alt' => $product->name,
        'label' => $i === 0 ? 'Main' : 'View '.($i + 1),
    ]);
    $mainImage = $images->first() ?? ['src' => asset('foodmart/images/singel-product-item.jpg'), 'alt' => $product->name, 'label' => 'Main'];
    $gallery = $images->all();

    // Har bir faol variant = bitta "pack" tugmasi.
    $packs = $variants->map(fn ($v) => [
        'key' => (string) $v->id,
        'label' => $v->name,
        'name' => $v->name,
        'price' => (float) $v->final_price,
        'old' => $v->has_discount ? (float) $v->price : null,
        'badge' => $v->has_discount ? '-'.$v->discount_percentage.'%' : null,
        'per' => (float) $v->final_price,
        'note' => $v->has_discount ? 'Save '.$v->discount_percentage.'%' : ((float) $v->stock > 0 ? 'In stock' : 'Sold out'),
    ])->all();

    $info = [
        'name' => $product->name,
        'farm' => $product->brand->name,
        'tagline' => $product->description ?: 'Fresh and carefully selected.',
        'was' => $packs[0]['old'],
    ];

@endphp

<div class="flex flex-col w-full text-on-surface">
<div class="relative w-full max-w-[1600px] mx-auto px-margin-mobile md:px-margin">
    {{-- Ambient glows --}}
    <div class="absolute top-12 left-1/4 w-96 h-96 bg-accent-hyper-emerald/5 rounded-full blur-[140px] pointer-events-none -z-10"></div>
    <div class="absolute top-80 right-10 w-80 h-80 bg-accent-electric-lime/5 rounded-full blur-[120px] pointer-events-none -z-10"></div>

    {{-- Breadcrumb + status --}}
    <section class="py-space-md flex flex-wrap items-center justify-between gap-space-sm">
        <x-breadcrumbs :items="$breadcrumbs" />
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-12 gap-gutter py-space-md">
        @include('partials.product-gallery')
        @include('partials.product-purchase')
    </section>

    @include('partials.product-related')

    @include('partials.cart-toast')
</div>
</div>
@endsection
