@props(['products', 'cols' => 'lg:grid-cols-4'])

<div class="grid grid-cols-1 sm:grid-cols-2 {{ $cols }} gap-6">
    @foreach ($products as $product)
        @php($variant = $product->cardVariant())
        @continue(! $variant)

        <x-product-card
            :id="$variant->id"
            :product-id="$product->id"
            :href="route('products.show', $product->slug)"
            :img="$product->mainImage?->url"
            :category="$product->category->name"
            :title="$product->name"
            :price="$variant->final_price"
            :old-price="$variant->has_discount ? $variant->price : null"
            :badge="$variant->has_discount ? '-'.$variant->discount_percentage.'%' : null"
        />
    @endforeach
</div>
