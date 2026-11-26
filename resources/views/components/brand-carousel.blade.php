@props(['brands' => []])

@if (count($brands) > 0)
    <section class="mt-14" data-brand-carousel aria-roledescription="carousel" aria-label="Featured brands">
        <x-section-heading eyebrow="Our partners" title="Featured Brands" sub="Makers and growers we trust" :href="route('brands.index')" cta="All brands" />

        <div class="relative">
            <button type="button" data-carousel-prev aria-label="Previous brands"
                class="absolute -left-3 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-border-glass bg-surface-carbon/95 text-text-crisp shadow-xl backdrop-blur transition-all hover:border-primary/60 hover:text-primary disabled:pointer-events-none disabled:opacity-30 md:flex sm:-left-5">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>

            <div data-carousel-track tabindex="0" role="group" aria-label="Brands, use the left and right arrow keys to browse"
                class="flex cursor-grab snap-x snap-mandatory -mx-4 -mb-4 -mt-6 gap-5 overflow-x-auto scroll-smooth scroll-px-4 px-4 pb-8 pt-6 [scrollbar-width:none] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 [&::-webkit-scrollbar]:hidden">
                @foreach ($brands as $brand)
                    <x-brand-card :brand="$brand" data-carousel-item class="w-[17rem] shrink-0 snap-start sm:w-80" />
                @endforeach
            </div>

            <button type="button" data-carousel-next aria-label="Next brands"
                class="absolute -right-3 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-border-glass bg-surface-carbon/95 text-text-crisp shadow-xl backdrop-blur transition-all hover:border-primary/60 hover:text-primary disabled:pointer-events-none disabled:opacity-30 md:flex sm:-right-5">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </section>

    @once
        @push('scripts')
            <script src="{{ asset('js/brand-carousel.js') }}"></script>
        @endpush
    @endonce
@endif
