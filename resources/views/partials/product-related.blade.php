{{-- $related: ProductController dan (Product collection), $product: joriy mahsulot --}}
@if ($related->isNotEmpty())
<section class="py-space-xl">
    <div class="flex flex-col gap-space-lg">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-sm">
            <div>
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-electric-lime">You may also like</span>
                <h2 class="font-headline-md text-headline-md text-text-crisp font-semibold tracking-tight">More from {{ $product->category->name }}</h2>
            </div>
            <a class="font-label-md text-label-md uppercase tracking-wider text-accent-hyper-emerald hover:underline flex items-center gap-1" href="{{ route('catalog.category', $product->category->slug) }}">Browse the whole category <span class="material-symbols-outlined text-[16px]">arrow_forward</span></a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
            @foreach ($related as $r)
                @php($rv = $r->cardVariant())
                @continue(! $rv)

                <x-recommend-card
                    :href="route('products.show', $r->slug)"
                    :name="$r->name"
                    :tag="$rv->has_discount ? '-'.$rv->discount_percentage.'%' : $r->category->name"
                    :kicker="$r->brand->name"
                    :meta="$rv->name"
                    :price="$rv->final_price"
                    :img="$r->mainImage?->url ?? asset('foodmart/images/singel-product-item.jpg')"
                    :alt="$r->name"
                />
            @endforeach
        </div>
    </div>
</section>
@endif
