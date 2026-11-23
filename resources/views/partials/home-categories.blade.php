{{-- $categories: HomeController dan (Category::rootsWithCounts) --}}
@if ($categories->isNotEmpty())
    <section class="mt-14">
        <x-section-heading eyebrow="Browse aisles" title="Shop by Category" sub="Everything from the farm gate to your pantry" :href="route('catalog.index')" cta="All Products" />
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach ($categories as $cat)
                <x-category-tile
                    :name="$cat->name"
                    :img="$cat->icon_url"
                    :count="$cat->items_count"
                    :href="route('catalog.category', $cat->slug)"
                />
            @endforeach
        </div>
    </section>
@endif
