@extends('layouts.app')

@section('title', 'Settings - FoodMart')

@section('content')
@php
    $btn = 'water-ripple-target rounded-2xl border border-border-glass bg-surface-carbon px-4 py-3 text-left text-sm text-text-crisp transition-colors hover:border-primary/50 aria-pressed:border-primary aria-pressed:bg-primary/10 aria-pressed:text-text-crisp';
    $themes = [
        ['dark', 'Dark', 'Original dark green', ['#111714', '#00f59b', '#d2ff00']],
        ['mint', 'Light mint', 'Soft light green', ['#f4fbf7', '#10b981', '#84cc16']],
        ['lavender', 'Light lavender', 'Soft light violet', ['#f7f4ff', '#a78bfa', '#d946ef']],
    ];
    $fonts = [
        ['jakarta', 'Plus Jakarta Sans', "'Plus Jakarta Sans', sans-serif"],
        ['hanken', 'Hanken Grotesk', "'Hanken Grotesk', sans-serif"],
        ['inter', 'Inter', "'Inter', sans-serif"],
        ['atkinson', 'Atkinson (easy to read)', "'Atkinson Hyperlegible', sans-serif"],
        ['system', 'System default', 'system-ui, sans-serif'],
        ['serif', 'Lora (serif)', "'Lora', Georgia, serif"],
        ['mono', 'JetBrains Mono', "'JetBrains Mono', monospace"],
    ];
@endphp
<x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Settings', 'url' => null]]" />

<div class="mt-6">
    <h1 class="font-display font-extrabold text-3xl text-text-crisp">Settings</h1>
    <p class="mt-1 text-sm text-text-muted">
        Appearance settings are saved in this browser and apply to the whole site.
        @guest Sign in to keep your profile photo on your account. @endguest
    </p>
</div>

<div class="mt-8 space-y-6" id="settings-root">

    {{-- PROFIL RASMI --}}
    <section class="dark-glass-card rounded-3xl p-6" id="avatar-section">
        <h2 class="font-display font-bold text-xl text-text-crisp">Profile photo</h2>
        <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
            <x-avatar :user="$user" :initial="true" size="w-24 h-24" text="text-4xl" class="border border-border-glass" id="settings-avatar" />

            <div class="flex-1 space-y-3">
                @auth
                    <form method="POST" action="{{ route('settings.avatar.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3" id="avatar-form">
                        @csrf
                        <input type="file" name="avatar" id="avatar-input" accept="image/png,image/jpeg,image/webp" required
                            class="block max-w-full text-sm text-text-muted file:mr-3 file:rounded-full file:border-0 file:bg-primary file:px-4 file:py-2 file:text-xs file:font-bold file:text-black hover:file:bg-emerald-400">
                        <button type="submit" class="water-ripple-target rounded-full bg-primary px-5 py-2 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors hover:bg-emerald-400">Upload</button>
                    </form>
                    @if ($user->avatar_path)
                        <form method="POST" action="{{ route('settings.avatar.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-red-400/40 px-4 py-1.5 text-xs font-bold text-red-300 transition-colors hover:bg-red-500/10">Remove photo</button>
                        </form>
                    @endif
                    @error('avatar')
                        <p class="text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-text-muted">JPG, PNG or WebP, up to 4 MB. It is cropped to a square and shown in the header, your profile, and the admin/partner panels.</p>
                @else
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" id="guest-avatar-input" accept="image/png,image/jpeg,image/webp,image/gif"
                            class="block max-w-full text-sm text-text-muted file:mr-3 file:rounded-full file:border-0 file:bg-primary file:px-4 file:py-2 file:text-xs file:font-bold file:text-black hover:file:bg-emerald-400">
                        <button type="button" data-guest-avatar-remove class="hidden rounded-full border border-red-400/40 px-4 py-1.5 text-xs font-bold text-red-300 transition-colors hover:bg-red-500/10">Remove photo</button>
                    </div>
                    <p id="guest-avatar-error" class="hidden text-xs text-red-400" role="alert"></p>
                    <p class="text-xs text-text-muted">As a guest, your photo stays only in this browser. <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Sign in</a> to save it to your account.</p>
                @endauth
            </div>
        </div>
    </section>

    {{-- MAVZU --}}
    <section class="dark-glass-card rounded-3xl p-6">
        <h2 class="font-display font-bold text-xl text-text-crisp">Theme</h2>
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3" role="group" aria-label="Theme">
            @foreach ($themes as [$key, $label, $hint, $swatch])
                <button type="button" data-pref="theme" data-value="{{ $key }}" aria-pressed="false" class="{{ $btn }}">
                    <span class="flex items-center gap-1.5">
                        @foreach ($swatch as $c)
                            <span class="h-5 w-5 rounded-full border border-black/10" style="background: {{ $c }}"></span>
                        @endforeach
                    </span>
                    <span class="mt-2 block font-bold">{{ $label }}</span>
                    <span class="block text-xs text-text-muted">{{ $hint }}</span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- SHRIFT VA O'LCHAM --}}
    <section class="dark-glass-card rounded-3xl p-6">
        <h2 class="font-display font-bold text-xl text-text-crisp">Fonts &amp; text size</h2>

        <h3 class="mt-5 text-[11px] font-mono font-bold uppercase tracking-widest text-primary">Text font</h3>
        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" role="group" aria-label="Text font">
            @foreach ($fonts as [$key, $label, $family])
                <button type="button" data-pref="font" data-value="{{ $key }}" aria-pressed="false" class="{{ $btn }}" style="font-family: {{ $family }}">
                    <span class="block font-bold">{{ $label }}</span>
                    <span class="block text-xs text-text-muted">Fresh organic harvest 0123</span>
                </button>
            @endforeach
        </div>

        <h3 class="mt-6 text-[11px] font-mono font-bold uppercase tracking-widest text-primary">Headings</h3>
        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3" role="group" aria-label="Headings font">
            <button type="button" data-pref="heading" data-value="display" aria-pressed="false" class="{{ $btn }}"><span class="block font-bold" style="font-family: 'Space Grotesk', sans-serif">Space Grotesk</span><span class="block text-xs text-text-muted">Default</span></button>
            <button type="button" data-pref="heading" data-value="body" aria-pressed="false" class="{{ $btn }}"><span class="block font-bold">Same as text</span><span class="block text-xs text-text-muted">Uses the text font</span></button>
            <button type="button" data-pref="heading" data-value="serif" aria-pressed="false" class="{{ $btn }}"><span class="block font-bold" style="font-family: 'Lora', Georgia, serif">Lora (serif)</span><span class="block text-xs text-text-muted">Classic look</span></button>
        </div>

        <h3 class="mt-6 text-[11px] font-mono font-bold uppercase tracking-widest text-primary">Text size</h3>
        <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4" role="group" aria-label="Text size">
            @foreach ([['sm', 'Small', '14px'], ['md', 'Default', '16px'], ['lg', 'Large', '18px'], ['xl', 'Extra large', '20px']] as [$key, $label, $px])
                <button type="button" data-pref="size" data-value="{{ $key }}" aria-pressed="false" class="{{ $btn }}">
                    <span class="block font-bold">{{ $label }}</span>
                    <span class="block text-xs text-text-muted">{{ $px }}</span>
                </button>
            @endforeach
        </div>

        <div class="mt-6 rounded-2xl border border-border-glass bg-surface-carbon p-5">
            <p class="text-[10px] font-mono uppercase tracking-widest text-text-muted">Preview</p>
            <p class="mt-2 font-display text-2xl font-extrabold text-text-crisp">Fresh organic market</p>
            <p class="mt-1 text-text-muted">Crisp greens, artisan sourdough and seasonal fruit, delivered to your door the same day.</p>
        </div>
    </section>

    {{-- ANIMATSIYA / KURSOR --}}
    <section class="dark-glass-card rounded-3xl p-6">
        <h2 class="font-display font-bold text-xl text-text-crisp">Motion &amp; effects</h2>
        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ([
                ['motion', 'Animations', 'Reduced turns off most movement and the glowing cursor.', [['full', 'Full'], ['reduced', 'Reduced']]],
                ['cursor', 'Glowing cursor', 'The water-drop trail that follows your mouse.', [['on', 'On'], ['off', 'Off']]],
                ['ripple', 'Button ripples', 'The water ripple when you click a button.', [['on', 'On'], ['off', 'Off']]],
                ['ambient', 'Background glow', 'The soft colored light behind the page.', [['on', 'On'], ['off', 'Off']]],
            ] as [$key, $label, $hint, $opts])
                <div class="rounded-2xl border border-border-glass bg-surface-carbon p-4">
                    <p class="font-bold text-text-crisp">{{ $label }}</p>
                    <p class="text-xs text-text-muted">{{ $hint }}</p>
                    <div class="mt-3 flex gap-2" role="group" aria-label="{{ $label }}">
                        @foreach ($opts as [$val, $name])
                            <button type="button" @if ($key === 'cursor') data-cursor="{{ $val }}" @else data-pref="{{ $key }}" data-value="{{ $val }}" @endif aria-pressed="false" class="{{ $btn }} flex-1 !py-2 text-center font-bold">{{ $name }}</button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex justify-end">
        <button type="button" id="settings-reset" class="rounded-full border border-border-glass px-5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-text-muted transition-colors hover:border-primary/40 hover:text-primary">Reset appearance to default</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/settings.js') }}"></script>
@endpush
