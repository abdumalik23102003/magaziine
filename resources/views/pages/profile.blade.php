@extends('layouts.store')

@section('title', 'FoodMart - My Profile')

@push('scripts')
    <script src="{{ asset('js/pages/profile.js') }}"></script>
@endpush

@section('content')
@php
    // Temporary demo data - later pass these from a controller (auth user, orders, addresses...).
    $user = ['name' => 'Alexander Vance', 'handle' => '@alex.vance', 'node' => 'Node 04 San Francisco (Pacific Vault)', 'bio_key' => '#FM-9842-OB', 'since' => 'Oct 2023', 'orders' => 48, 'credits' => '34.50', 'avatar' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCc-_mHbsXpyQpZCtcDflkQj5w7ZKxB640hAA72X7l33aHErzWuAs70Zzxng1onav-mud_2ozdsf66-prA5sLVwUrq2kqBhv9j5EalkwdxxEGoW9QDig6Zi7tduSSl99DJsfh3bJQduXs9kRoRx9BW5UnPsscP-qIkwanNT499RdxG-ubq-fjN4QuoVrUYI6YgYwD88tEieXGgZ5E9QMwbjvJPt-JzAKV1L_9_NXoo'];
    $payload = [['name' => 'Hass Avocados', 'meta' => '4x • Sonoma', 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDX55aabs56jIA4VGkOUmYLu57ehqFE5V-oE7EIUoYRwyJSRLgN1PUCJIJfRGEIaN2ctebm8w4CVHC9IDRixsoNBuBuAjtKtFePZFc7E_jaAyipGYSQRCKQkZXlX9s3w5-d2eloR3PmPJniDcs_yPzi7IL5ald7V0dD5xeoWkJZjZqSplG5ffFrWTZDzc9DlJQ39toiD--pQQM5QQPxXLjbodOQzpACqo2c_lUB_JU', 'alt' => 'Macro cinematic shot of organic Hass avocados with rich textured dark green skin glistening in pristine laboratory lighting on black granite background'], ['name' => 'Alpine Strawberries', 'meta' => '250g • Big Sur', 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDZKd6vVy2PLWXVlVoimRSXjWvpNusfUcDukBZRBLXItn9ekJP76A21erdCDrUgjLVGoJ1GQkcZnEvhb7b1fOh9VhytorqqrIol06ieEG8C0eKrBjS6TslnE6gtFFiA94v5L2FsVfIZ0Qlzjlpo2Hn2On76RTBVQrWvUzrZHnCaDXAnGD-60K5vac3pjiA-Kx7xdErdtgLVx0dpSy5bfI6z76lJABcPVNCtXo0ogzA', 'alt' => 'Exquisite macro shot of wild Alpine strawberries glowing in vibrant red against pure obsidian background with fresh water dewdrops'], ['name' => 'Romanesco Crown', 'meta' => '1 Head • Salinas', 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDXZ0SNkELt9_W7UMk9eoLTCrmFBeLMVQKEBf0vAbPRzSrCsevwQEHH0DP-fNlein5LB1k3QV77kb_mo4RRsuadeRFe5JVoYpaqyg3ZVlFvLymfhQnE1bxleOMWhLstKoyuZ2fl0pM6m6-r3Ju5qz6rSLbuDgvLAJiDQDOnuIAGvWt_k8FyDcslPrjiBQ2tEMKBDGhsn5D0bhkujQz6Vf4UkI5eTCLinAKF3JESln0', 'alt' => 'Crisp freshly harvested organic Romanesco broccoli with geometric spiral florets displayed on dark slate with soft neon biological lighting'], ['name' => 'Valencia Oranges', 'meta' => '1.5kg • Ojai Grove', 'img' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuByOgF0x0IZoCnzePUxmtKP9DJ73PUztL_wIxQGHkDh-csfnaT4qX-79IcPCgBtm78Q6oewEtp_WuNsa6z6oXDoSNi91gqc2VLRS4FNwBzdm_2mBoVblvSIcsRbKvBgimbHuHM6q_2upGwKVTquh-08wJq30yMQfTWQpTDsn4RqzVklUg7szdYoZyHQO3WGOuhhGDTo5oFOhAnO1uDgIE4Wqt1jFqCNEx8lVlQ8OpU', 'alt' => 'High contrast cut citrus heirloom Valencia orange slice showing crystalline pulp vessels floating over dark reflective obsidian surface']];
    $orders = [['number' => 'FM-87412', 'status' => 'Delivered Yesterday', 'total' => '42.10', 'items' => 6, 'summary' => 'Mycelium Insulated Tote Retained for Recycling', 'variance' => '±0.2°C Max Dev', 'origin' => 'Marin Demeter Ranch'], ['number' => 'FM-86094', 'status' => 'Delivered 4 Days Ago', 'total' => '28.50', 'items' => 4, 'summary' => 'Cold-Pressed Greens & Raw Micro-Algae', 'variance' => '0.0°C Absolute', 'origin' => 'Central Coast Cellars'], ['number' => 'FM-84310', 'status' => 'Delivered 12 Days Ago', 'total' => '64.80', 'items' => 8, 'summary' => 'Biodynamic Botanical Pantry & Raw Ferments', 'variance' => '±0.1°C Max Dev', 'origin' => 'Napa Demeter Collective']];
    $address = ['street' => '742 Montgomery St', 'suite' => 'Penthouse Suite 14B', 'city' => 'San Francisco, CA 94111', 'note' => 'Leave with 14th floor concierge or place inside thermal mycelium cold tote bag at apartment doorway. Ring unit chime once.'];
    $standards = ['100% Demeter Biodynamic', 'Zero Synthetic Pesticides', 'Gluten-Free Bakery Only', 'Low-Sugar Citrus', 'High Polyphenol Olive Oils'];
    $allergens = ['Peanut-Free Verified', 'Shellfish Cross-Contam Shield'];
    $impact = ['done' => 21, 'goal' => 25, 'percent' => 84];
@endphp

<div class="flex flex-col w-full text-on-surface">
<div class="relative w-full overflow-hidden">
    {{-- Top ambient glows --}}
    <div class="absolute -top-40 left-1/4 w-[600px] h-[350px] bg-accent-hyper-emerald/5 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute -top-20 right-10 w-[420px] h-[280px] bg-accent-electric-lime/5 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="max-w-[1600px] mx-auto px-margin-mobile md:px-margin py-space-lg w-full flex flex-col gap-space-lg">
        @include('partials.profile-hero')
        @include('partials.profile-tabs')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter items-start">
            {{-- LEFT: orders and telemetry --}}
            <div class="lg:col-span-8 flex flex-col gap-space-lg">
                @include('partials.profile-dispatch')
                @include('partials.profile-orders')
                @include('partials.profile-presets')
            </div>
            {{-- RIGHT: preferences and security --}}
            @include('partials.profile-sidebar')
        </div>
    </div>
</div>
</div>
@endsection
