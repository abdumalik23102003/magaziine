@extends('layouts.app')

@section('title', 'Sign in - FoodMart')

@section('content')
<div class="mx-auto max-w-md py-10">
    <div class="dark-glass-card rounded-3xl p-8">
        <h1 class="font-display font-extrabold text-3xl text-text-crisp">Sign in</h1>
        <p class="mt-1 text-sm text-text-muted">Welcome back. Your cart and favorites will follow you.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="email" required />
            <x-field name="password" label="Password" type="password" autocomplete="current-password" required />

            <label class="flex items-center gap-3 text-sm text-text-crisp cursor-pointer">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-border-glass bg-surface-carbon text-primary focus:ring-primary/30">
                Remember me
            </label>

            <button type="submit" class="water-ripple-target w-full rounded-full bg-primary hover:bg-emerald-400 py-3 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Sign in</button>
        </form>

        <p class="mt-6 text-center text-sm text-text-muted">
            New here? <a href="{{ route('register') }}" class="font-bold text-primary hover:underline">Create an account</a>
        </p>
    </div>
</div>
@endsection
