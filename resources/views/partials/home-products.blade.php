{{-- Reusable product grid section. Pass: $eyebrow, $title, $sub, $cta, $items (array), $cols (optional grid class) --}}
<section class="mt-14">
    <x-section-heading :eyebrow="$eyebrow" :title="$title" :sub="$sub" href="#" :cta="$cta" />
    <div class="grid grid-cols-1 sm:grid-cols-2 {{ $cols ?? 'lg:grid-cols-4' }} gap-6">
        @foreach ($items as $product)
            @if (is_array($product))
                <x-product-card
                    :id="$product['id']"
                    :img="$product['img']"
                    :glow="$product['glow'] ?? '0,245,155'"
                    :category="$product['category']"
                    :title="$product['title']"
                    :rating="$product['rating'] ?? 5"
                    :reviews="$product['reviews'] ?? 0"
                    :price="$product['price']"
                    :old-price="$product['oldPrice'] ?? null"
                    :badge="$product['badge'] ?? null"
                />
            @else
                @php
                    $variant = $product->variants->first();
                @endphp

                @if ($variant)
                    <x-product-card
                        :id="$product->id"
                        :img="$product->mainImage?->path"
                        :category="$product->category->name"
                        :title="$product->name"
                        :price="$variant->price"
                    />
                @endif
            @endif
        @endforeach
    </div>
</section>
