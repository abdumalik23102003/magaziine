{{-- Reusable product grid section. Pass: $eyebrow, $title, $sub, $cta, $items (Product collection), $cols (optional grid class) --}}
<section class="mt-14">
    <x-section-heading :eyebrow="$eyebrow" :title="$title" :sub="$sub" href="#" :cta="$cta" />
    <div class="grid grid-cols-1 sm:grid-cols-2 {{ $cols ?? 'lg:grid-cols-4' }} gap-6">
        @foreach ($items as $product)
            @php($variant = $product->cardVariant())
            @continue(! $variant)

            <x-product-card
                :id="$product->id"
                :img="$product->mainImage?->url"
                :category="$product->category->name"
                :title="$product->name"
                :price="$variant->final_price"
                :old-price="$variant->has_discount ? $variant->price : null"
                :badge="$variant->has_discount ? '-'.$variant->discount_percentage.'%' : null"
            />
        @endforeach
    </div>
</section>
