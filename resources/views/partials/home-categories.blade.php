{{-- $categories comes from pages/home.blade.php --}}
<section class="mt-14">
    <x-section-heading eyebrow="Browse aisles" title="Shop by Category" sub="Everything from the farm gate to your pantry" href="#" cta="All Categories" />
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach ($categories as $cat)
            <x-category-tile :name="$cat['name']" :icon="$cat['icon']" :count="$cat['count']" />
        @endforeach
    </div>
</section>
