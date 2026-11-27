@extends('layouts.app')

@section('title', 'Enter your code - FoodMart')

@section('content')
<div class="mx-auto max-w-md py-10">
    <div class="dark-glass-card rounded-3xl p-8">
        <h1 class="font-display font-extrabold text-3xl text-text-crisp">Enter your code</h1>
        <p class="mt-1 text-sm text-text-muted">We sent a 6-digit code to <span class="font-bold text-text-crisp">{{ $email }}</span>. It expires in {{ \App\Services\LoginCodeService::TTL_MINUTES }} minutes.</p>

        @if ($errors->has('auth') || $errors->has('email'))
            <p class="mt-4 rounded-xl border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-300">{{ $errors->first('auth') ?: $errors->first('email') }}</p>
        @endif

        <form method="POST" action="{{ route('login.code.confirm') }}" class="mt-8 space-y-5">
            @csrf
            <x-field name="code" label="6-digit code" autocomplete="one-time-code" inputmode="numeric" maxlength="12" pattern="[0-9 ]*" required />

            <button type="submit" class="water-ripple-target w-full rounded-full bg-primary hover:bg-emerald-400 py-3 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Sign in</button>
        </form>

        <form method="POST" action="{{ route('login.code.store') }}" class="mt-5 text-center">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="text-sm font-bold text-primary hover:underline">Send a new code</button>
        </form>

        <p class="mt-4 text-center text-sm text-text-muted">
            Wrong email? <a href="{{ route('login.code') }}" class="font-bold text-primary hover:underline">Start over</a>
        </p>
    </div>
</div>
@endsection
