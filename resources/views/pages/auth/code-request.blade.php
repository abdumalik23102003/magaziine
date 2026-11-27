@extends('layouts.app')

@section('title', 'Sign in with email code - FoodMart')

@section('content')
<div class="mx-auto max-w-md py-10">
    <div class="dark-glass-card rounded-3xl p-8">
        <h1 class="font-display font-extrabold text-3xl text-text-crisp">Email code</h1>
        <p class="mt-1 text-sm text-text-muted">We will email you a 6-digit code. No password needed, and a new account is created automatically.</p>

        <form method="POST" action="{{ route('login.code.store') }}" class="mt-8 space-y-5">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="email" required />

            <button type="submit" class="water-ripple-target w-full rounded-full bg-primary hover:bg-emerald-400 py-3 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Send code</button>
        </form>

        <p class="mt-6 text-center text-sm text-text-muted">
            Prefer a password? <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Sign in</a>
        </p>
    </div>
</div>
@endsection
