@props(['rating' => 5, 'size' => 18])

@php $r = round($rating * 2) / 2; @endphp
<div {{ $attributes->merge(['class' => 'flex text-accent-hyper-emerald']) }}>
    @for ($i = 1; $i <= 5; $i++)
        <span class="material-symbols-outlined text-[{{ $size }}px]" style="font-variation-settings: 'FILL' {{ $r >= $i - 0.5 ? 1 : 0 }};">{{ $r >= $i ? 'star' : ($r >= $i - 0.5 ? 'star_half' : 'star') }}</span>
    @endfor
</div>
