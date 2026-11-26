@props(['value' => 0, 'size' => 'text-sm'])

@php($filled = (int) round((float) $value))

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 text-amber-400 '.$size]) }} role="img" aria-label="{{ number_format((float) $value, 1) }} out of 5">
    @for ($i = 1; $i <= 5; $i++)
        <i class="bi {{ $i <= $filled ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
    @endfor
</span>
