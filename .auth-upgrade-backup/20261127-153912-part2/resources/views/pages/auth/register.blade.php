@extends('layouts.app')

@section('title', 'Create account - FoodMart')

@section('content')
<div class="mx-auto max-w-md py-10">
    <div class="dark-glass-card rounded-3xl p-8">
        <h1 class="font-display font-extrabold text-3xl text-text-crisp">Create account</h1>
        <p class="mt-1 text-sm text-text-muted">Checkout, order history and saved favorites in one place.</p>

        <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
            @csrf
            <x-field name="name" label="Full name" autocomplete="name" required />
            <x-field name="email" label="Email" type="email" autocomplete="email" required />
            <x-field name="password" label="Password (min 8 characters)" type="password" autocomplete="new-password" required />
            <x-field name="password_confirmation" label="Repeat password" type="password" autocomplete="new-password" required />

            <button type="submit" class="water-ripple-target w-full rounded-full bg-primary hover:bg-emerald-400 py-3 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Create account</button>
        </form>

        <p class="mt-6 text-center text-sm text-text-muted">
            Already registered? <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Sign in</a>
        </p>
    </div>
</div>
@endsection
