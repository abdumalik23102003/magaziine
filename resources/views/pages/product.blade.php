@extends('layouts.store')

@section('title', 'FoodMart - '.$product->name)

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

    // DEMO: baho, sharh va "harvest" matni uchun hali jadval yo'q.
    $info = [
        'name' => $product->name,
        'farm' => $product->brand->name,
        'tagline' => $product->description ?: 'Fresh and carefully selected.',
        'rating' => 4.8,
        'reviews' => 124,
        'was' => $packs[0]['old'],
        'harvest' => '6h 14m ago • Santa Barbara Coastal Foothills',
    ];

    // DEMO (pastdagilar hali bazada yo'q): ripeness, timeline, reviews.
    $ripeness = [['title' => 'Ready Tonight', 'sub' => 'Lush & Butter-Soft', 'dot' => 'bg-accent-electric-lime'], ['title' => 'Peak in 2 Days', 'sub' => 'Optimal Texture • Rec', 'dot' => 'bg-accent-hyper-emerald'], ['title' => 'Staggered', 'sub' => '2 Firm + 2 Ripe', 'dot' => 'bg-outline']];
    $timeline = [['time' => '05:30 AM PST', 'title' => 'Sunrise Hand-Harvest', 'text' => 'Rancho La Loma high-elevation orchard, Santa Barbara coastal hills. Harvested during peak biological turgor pressure before solar heat exposure.'], ['time' => '07:15 AM PST', 'title' => 'Rapid Hydro-Cooling Intake', 'text' => 'Avocados transferred to field-side refrigerated intake bath. Core internal fruit temperature actively reduced to 3.2°C in under 18 minutes to halt cellular decay.'], ['time' => '09:40 AM PST', 'title' => 'Mycelium Cold-Seal Packaging', 'text' => 'Individual vacuum stabilization inside compostable biomimetic mycelium pouches. Integrated RFID continuous thermal logging sensors activated.'], ['time' => 'CURRENT STATUS', 'title' => 'Metro Cold-Vault Staged', 'text' => 'Staged at Metro Hub Cold-Vault Node 04 (Bay 12-F). Thermal sensor confirms unbroken 3.4°C baseline. Awaiting final EV routing dispatch.', 'current' => true]];
    $reviews  = [['name' => 'Dr. Julian Vance', 'badge' => 'Verified Lot #FM-4390', 'rating' => 5, 'text' => 'The fat density is immediately noticeable compared to regular supermarket avocados. Zero stringy fibrous tissue, completely butter-smooth right against the skin. Arrived cold to the touch at 4°C exactly.', 'meta' => 'Delivered 2 days ago • Santa Monica'], ['name' => 'Chef Elena Rostova', 'badge' => 'Chef Crate Client', 'rating' => 5, 'text' => 'We use these for our raw crudo dishes. The oleic acidity gives a lingering creamy nuttiness that doesn\'t need heavy citrus masking. The staggered maturation pack was perfectly timed for our service.', 'meta' => 'Delivered 4 days ago • Beverly Hills Kitchen'], ['name' => 'Marcus K.', 'badge' => 'Weekly Regimen', 'rating' => 4.5, 'text' => 'The mycelium packaging keeps the climate intact better than any ice-pack setup. Scanning the QR code to read the morning harvest time is next level.', 'meta' => 'Delivered 1 week ago • Malibu Colony']];
@endphp

<div class="flex flex-col w-full text-on-surface">
<div class="relative w-full max-w-[1600px] mx-auto px-margin-mobile md:px-margin">
    {{-- Ambient glows --}}
    <div class="absolute top-12 left-1/4 w-96 h-96 bg-accent-hyper-emerald/5 rounded-full blur-[140px] pointer-events-none -z-10"></div>
    <div class="absolute top-80 right-10 w-80 h-80 bg-accent-electric-lime/5 rounded-full blur-[120px] pointer-events-none -z-10"></div>

    {{-- Breadcrumb + status --}}
    <section class="py-space-md flex flex-wrap items-center justify-between gap-space-sm">
        <x-breadcrumbs :items="$breadcrumbs" />
        <div class="flex items-center gap-space-sm">
            <div class="flex items-center gap-1.5 px-space-sm py-0.5 rounded-full bg-surface-slate text-accent-hyper-emerald font-label-sm text-label-sm uppercase tracking-widest shadow-sm"><span class="w-1.5 h-1.5 rounded-full bg-accent-hyper-emerald animate-pulse"></span> Vault Lot #FM-4402</div>
            <div class="hidden sm:flex items-center gap-1 text-outline font-label-sm text-label-sm uppercase tracking-wider"><span class="material-symbols-outlined text-[14px] text-accent-electric-lime">sensor_occupied</span> 34 Members viewing</div>
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-12 gap-gutter py-space-md">
        @include('partials.product-gallery')
        @include('partials.product-purchase')
    </section>

    @include('partials.product-audit')
    @include('partials.product-reviews')
    @include('partials.product-related')

    @include('partials.cart-toast')
</div>
</div>
@endsection
