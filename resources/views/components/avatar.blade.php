@props(['user' => null, 'size' => 'w-11 h-11', 'text' => 'text-base', 'initial' => false])

{{--
    Profil rasmi. Kirgan foydalanuvchi: serverdagi rasm, bo'lmasa bosh harf (initial) yoki belgi.
    Mehmon: faqat shu brauzerda saqlangan rasm (public/js/prefs.js to'ldiradi), bo'lmasa belgi.
--}}
@php($url = $user?->avatarUrl())

@if ($user)
    <span {{ $attributes->class([$size, $text, 'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-display font-black', 'bg-gradient-to-tr from-primary to-accent-lime text-black' => $url === null && $initial]) }}>
        @if ($url)
            <img src="{{ $url }}" alt="{{ $user->name }}" class="h-full w-full object-cover" loading="lazy">
        @elseif ($initial)
            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
        @else
            <i class="bi bi-person text-lg"></i>
        @endif
    </span>
@else
    <span data-guest-avatar {{ $attributes->class([$size, $text, 'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full']) }}>
        <img data-avatar-img alt="" class="hidden h-full w-full object-cover">
        <i data-avatar-fallback class="bi bi-person text-lg"></i>
    </span>
@endif
