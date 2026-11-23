@props(['name', 'tag', 'tagClass' => 'text-accent-hyper-emerald', 'kicker', 'meta', 'price', 'img', 'alt' => '', 'addName' => null, 'href' => null])

<div class="bg-surface-carbon p-space-md rounded-xl shadow-md group hover:bg-surface-slate transition-all flex flex-col justify-between">
    <div class="relative h-44 rounded-lg overflow-hidden bg-surface-slate mb-space-sm">
        <span class="absolute top-2 right-2 z-10 font-label-sm text-label-sm uppercase bg-surface-obsidian/90 px-2 py-0.5 rounded {{ $tagClass }} font-semibold">{{ $tag }}</span>
        <img alt="{{ $alt }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="{{ $img }}"/>
    </div>
    <div>
        <div class="font-label-sm text-label-sm uppercase text-text-muted tracking-wider">{{ $kicker }}</div>
        <div class="font-title text-title text-text-crisp font-semibold mt-0.5">
            @if ($href)
                <a href="{{ $href }}" class="hover:text-accent-hyper-emerald transition-colors">{{ $name }}</a>
            @else
                {{ $name }}
            @endif
        </div>
        <div class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">{{ $meta }}</div>
    </div>
    <div class="flex items-center justify-between mt-space-md pt-space-xs">
        <span class="font-headline-sm text-headline-sm text-text-crisp font-bold">${{ number_format($price, 2) }}</span>
        <button type="button" class="quick-add px-space-md py-1.5 rounded-lg bg-surface-elevated hover:bg-accent-hyper-emerald hover:text-surface-obsidian text-accent-hyper-emerald font-label-md text-label-md uppercase tracking-wider transition-colors flex items-center gap-1" data-name="{{ $addName ?? $name }}" data-price="{{ $price }}">
            <span class="material-symbols-outlined text-[16px]">add</span> Add
        </button>
    </div>
</div>
