@props(['items'])

@php
    $items = array_values($items);
    $last = array_key_last($items);

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(fn ($item, $i) => array_filter([
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $item['label'],
            'item' => $item['url'] ?? null,
        ]), $items, array_keys($items)),
    ];
@endphp

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-[11px] font-mono uppercase tracking-wider']) }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-text-muted">
        @foreach ($items as $i => $item)
            <li class="flex items-center gap-2 min-w-0">
                @if ($i > 0)
                    <span aria-hidden="true" class="text-zinc-600">/</span>
                @endif

                @if (! empty($item['url']) && $i !== $last)
                    <a href="{{ $item['url'] }}" class="hover:text-primary transition-colors">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="text-text-crisp font-semibold truncate max-w-[16rem] sm:max-w-none">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
