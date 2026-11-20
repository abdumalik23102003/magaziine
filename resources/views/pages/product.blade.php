@extends('layouts.store')

@section('title', 'FoodMart - Hass Creamy Avocados')

@push('scripts')
    <script src="{{ asset('js/pages/product.js') }}"></script>
@endpush

@section('content')
@php
    // Temporary demo data - later pass these from a controller / Product model.
    $product  = ['name' => 'Hass Creamy Avocados', 'farm' => 'Demeter Certified • Rancho La Loma Micro-Orchard', 'tagline' => 'High-Elevation Micro-Lot • Biodynamic Organic • Cold-Stored under 4h from morning harvest.', 'rating' => 4.8, 'reviews' => 124, 'was' => 5.99, 'harvest' => '6h 14m ago • Santa Barbara Coastal Foothills'];
    $gallery  = [['src' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDKTSqqtL67LIXQ08yv8XahXA4SfgZRpbse7w9tQEJXBwASswzDLv7NBMeDGuNb-0z7iFVQLzsIuGXsRFO_nct6BJbFU7ObRSaoI5dc0Cv-BBC9OfAXqAgZ1rZA8-77Ehbj1fHCpmC0wAAYhIvfIog_QjW5kHujqyqTvj1b-SngY89_DL8S1UIobcpOe-CyyE5zkNz8LpCdDwvhcgGf5E-nV1LBh9sglj7o8CqSfaA', 'alt' => 'Hass Creamy Avocado Cross Section', 'label' => ''], ['src' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBZzrLIf3USjHvBXXkaAlRCU2DubwwLIeOdpe56NBJqMAocbqKcXVxGhtca_XFuICoL87D4HClXL-dWgneR_N4UUPDsfjP3QTfpB47SLiR2yr51V3wtXEo1h9DkBsqmUSOsvNSMgW5YiGbdUmI6Wa1BiY4KfY9kkqLvAbUaiyEjrOjdmJSUhf5tNFsIrFLShHIwt_Ec1PgpWcsywMsb8HQjwjnQLX4LC3YanCQYtn8', 'alt' => 'Halved open fruit', 'label' => 'Flesh Core'], ['src' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuANzL2hpWsvsb_GrYw305YpXCvSzJC9yOsUmum8Yw__lFwwZm5heNXS2RymZpIvjywgXzrfa1K5gCzGF5015Fw_fgnXy-PtwUHHgg6AVWUgPQEJRBHH4moa6_b6gh0lSgGN0S-jDKHPX91gQJL6mCgG_j4-Dft7gNtwsrJuWf_zgTFcRNMrCVTsTjKJqHPJHe5W0K4mNE5TW1wEYAjB5f3VKY5hhtyJ38WXH-fKs5E', 'alt' => 'Exterior skin texture', 'label' => 'Rind Texture'], ['src' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB5zCUjzwp3q-mFm2VHgLGpj-cUuQVwok2NMisQl8KJhjZmSAJ8FvRVUZ-qW8g6Ou_R66vzzmlDyVXcD4yEk6Du92v9b88yWjNAGeMHbcDFvLl0rspHzdR8wAzkQEMhXQjhItRGI_DDyo8GDEM56Lg93a8GW8jftBPS0FYH1c9pE5zdbwsWMZNxOcBFGxAitIfQ51lyffrOG-QfwVHhCMsTHTbr8RCGc_MpHkdEzxg', 'alt' => 'Packaging pouch', 'label' => 'Cryo Pouch'], ['src' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCFmF55gGwk22Q-9kLDNuZd479kvewfNZayoEZHroT-shPZORdoVRAS1n5IptKjN82zwhOQwWaeTU3DbE9zoorRXfv7ocbF-AztWCgpnoRKOhrTB4DIhIic0o5bFU1P3BnMI6t-_y-VAwuc2pgjih3X5Fo9knfXOQg_iZWKKetRASFhe_4fQlmpDDeOza6HiGcJWBUez3sht3duIg8rsFOeJMTTJJ641kOPtt_j5JY', 'alt' => 'Farm branch origin', 'label' => 'Rancho Origin']];
    $mainImage = array_shift($gallery); // first entry is the big hero image, the rest are thumbnails
    $packs    = [['key' => 'standard', 'label' => '4-Pack', 'name' => 'Standard 4-Pack', 'price' => 4.49, 'per' => 1.12, 'note' => '$1.12/ea'], ['key' => 'pantry', 'label' => '8-Pack', 'name' => 'Pantry 8-Pack', 'price' => 8.2, 'per' => 1.02, 'note' => 'Save 10%'], ['key' => 'crate', 'label' => '24 Crate', 'name' => 'Chef Crate 24-Pack', 'price' => 22.5, 'per' => 0.94, 'note' => 'Pro Bulk']];
    $ripeness = [['title' => 'Ready Tonight', 'sub' => 'Lush & Butter-Soft', 'dot' => 'bg-accent-electric-lime'], ['title' => 'Peak in 2 Days', 'sub' => 'Optimal Texture • Rec', 'dot' => 'bg-accent-hyper-emerald'], ['title' => 'Staggered', 'sub' => '2 Firm + 2 Ripe', 'dot' => 'bg-outline']];
    $timeline = [['time' => '05:30 AM PST', 'title' => 'Sunrise Hand-Harvest', 'text' => 'Rancho La Loma high-elevation orchard, Santa Barbara coastal hills. Harvested during peak biological turgor pressure before solar heat exposure.'], ['time' => '07:15 AM PST', 'title' => 'Rapid Hydro-Cooling Intake', 'text' => 'Avocados transferred to field-side refrigerated intake bath. Core internal fruit temperature actively reduced to 3.2°C in under 18 minutes to halt cellular decay.'], ['time' => '09:40 AM PST', 'title' => 'Mycelium Cold-Seal Packaging', 'text' => 'Individual vacuum stabilization inside compostable biomimetic mycelium pouches. Integrated RFID continuous thermal logging sensors activated.'], ['time' => 'CURRENT STATUS', 'title' => 'Metro Cold-Vault Staged', 'text' => 'Staged at Metro Hub Cold-Vault Node 04 (Bay 12-F). Thermal sensor confirms unbroken 3.4°C baseline. Awaiting final EV routing dispatch.', 'current' => true]];
    $reviews  = [['name' => 'Dr. Julian Vance', 'badge' => 'Verified Lot #FM-4390', 'rating' => 5, 'text' => 'The fat density is immediately noticeable compared to regular supermarket avocados. Zero stringy fibrous tissue, completely butter-smooth right against the skin. Arrived cold to the touch at 4°C exactly.', 'meta' => 'Delivered 2 days ago • Santa Monica'], ['name' => 'Chef Elena Rostova', 'badge' => 'Chef Crate Client', 'rating' => 5, 'text' => 'We use these for our raw crudo dishes. The oleic acidity gives a lingering creamy nuttiness that doesn\'t need heavy citrus masking. The staggered maturation pack was perfectly timed for our service.', 'meta' => 'Delivered 4 days ago • Beverly Hills Kitchen'], ['name' => 'Marcus K.', 'badge' => 'Weekly Regimen', 'rating' => 4.5, 'text' => 'The mycelium packaging keeps the climate intact better than any ice-pack setup. Scanning the QR code to read the morning harvest time is next level.', 'meta' => 'Delivered 1 week ago • Malibu Colony']];
    $related  = [['name' => 'Sweet Alpine Strawberries', 'tag' => 'Biodynamic', 'tagClass' => 'text-accent-hyper-emerald', 'kicker' => 'High Elevation Berries', 'meta' => '1 lb pack • Brix 16.8°', 'price' => 3.79, 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDBR3jgrkDnaYUTs-OUphTRV0jkxRlewtSK11oFhE3NuBIS9nBcAS_P7TEyKqhl4H70wHfwbSsExNGfuNCB0RoxmhwBOx2JdMLetQNSj5_bZz7S--Tt_bDQGQFZvZ4PIcGYMsh8QyUm7qWDrPRmMCdPJfnbCesbj4C3yWYVrc-yMdVeX51UZivecQ7mHbIptUymT_vultFntT-sR8IQBS5QMzDf8qWWIoSXnN-v4LQ', 'alt' => 'Alpine Strawberries', 'addName' => 'Sweet Alpine Strawberries'], ['name' => 'Artisan Sourdough Boule', 'tag' => 'Artisanal Bake', 'tagClass' => 'text-accent-electric-lime', 'kicker' => 'Ancient Grain Culture', 'meta' => '850g • 36h Cold Ferment', 'price' => 6.49, 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC-ORSP-xhGz1jRGqZZhfb16q39zDXR03HP98p05ZzSig3tEFiEoo7vgWNMQu1lYetaELF2bI3hVYI05r35clPy3-uRP5KepZ_XjrlXhlVFitnZ6tVixySw8jqKKYnZTzgnIxkJ4XfqB-o1dpfoC3OEOkgD8paQH9hbsFnCYWVe61Ekm28uAmJjtPsBY8TylWH1_JDmUJR9mEabkHLd4VS_-OLiWGtJeXW1H9R7odw', 'alt' => 'Artisan Sourdough Boule', 'addName' => 'Artisan Sourdough Boule'], ['name' => 'Cold-Pressed Kale Tonic', 'tag' => 'Raw Pressed', 'tagClass' => 'text-accent-hyper-emerald', 'kicker' => 'Hydraulic Cold Extraction', 'meta' => '500ml • Raw Unpasteurized', 'price' => 4.99, 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAJrJcA-cQLGoQAmN50bhRn8nuc9NQ4NJ-rfIeBm3umSJJgWv7mhI5yccZDvcJh1T3qDrxo_WknbYbRjmkynkljzIAYP7acr36RXdPt59yqKbV_cH9uezp07sVUkjGJjfbESMS0dS8mXRFugernJ4tOHHrXxSawlhIJhp4nA7L0wUcBojZVdh_oICZrxRLefeC4Ql3QKmP1a8muMMADl9sXC-Wa6zRTbe4RbMeAM3g', 'alt' => 'Tuscan Kale Tonic', 'addName' => 'Cold-Pressed Kale Tonic'], ['name' => 'Crisp Farm Broccoli Crowns', 'tag' => 'Field Cut', 'tagClass' => 'text-text-crisp', 'kicker' => 'High Sulforaphane', 'meta' => '2 bunches • Direct Field Cut', 'price' => 2.29, 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCJdbIDhKX0X66p-CMpWQ2dhHOMkFBETQk2Z5AFMDjVRGehzZhDLLAgc9ifJLmW2kjpJ7SX9Rs0Lu7s_Cu1paalfmyvvKRqTwnBT8UsXGE4NO_VQiZst_SP097AMHd2iGRCS4W5NH6HklgrS5-ca41TddX-2TId2N66P14Vcd_sJrt5vOv75sv0nX5ZIVPrgNX-g1JCQpuwC2qihi_vJJYa5lJA7Dx2HQMzx6MeykM', 'alt' => 'Broccoli Crowns', 'addName' => 'Broccoli Crowns']];
@endphp

<div class="flex flex-col w-full text-on-surface">
<div class="relative w-full max-w-[1600px] mx-auto px-margin-mobile md:px-margin">
    {{-- Ambient glows --}}
    <div class="absolute top-12 left-1/4 w-96 h-96 bg-accent-hyper-emerald/5 rounded-full blur-[140px] pointer-events-none -z-10"></div>
    <div class="absolute top-80 right-10 w-80 h-80 bg-accent-electric-lime/5 rounded-full blur-[120px] pointer-events-none -z-10"></div>

    {{-- Breadcrumb + status --}}
    <section class="py-space-md flex flex-wrap items-center justify-between gap-space-sm">
        <nav aria-label="Breadcrumb" class="flex items-center gap-space-xs font-label-md text-label-md uppercase tracking-wider text-text-muted">
            <a class="hover:text-accent-hyper-emerald transition-colors" href="{{ url('/') }}">Home</a>
            <span class="text-outline-variant">/</span>
            <a class="hover:text-accent-hyper-emerald transition-colors" href="#">Biological Catalog</a>
            <span class="text-outline-variant">/</span>
            <a class="hover:text-accent-hyper-emerald transition-colors" href="#">Heirloom Orchard</a>
            <span class="text-outline-variant">/</span>
            <span class="text-text-crisp font-semibold">{{ $product['name'] }}</span>
        </nav>
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
