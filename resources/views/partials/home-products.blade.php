{{-- Pass: $eyebrow, $title, $sub, $cta, $items (Product collection), $href (optional), $cols (optional grid class) --}}
<section class="mt-14">
    <x-section-heading :eyebrow="$eyebrow" :title="$title" :sub="$sub" :href="$href ?? route('catalog.index')" :cta="$cta" />
    <x-product-grid :products="$items" :cols="$cols ?? 'lg:grid-cols-4'" />
</section>
