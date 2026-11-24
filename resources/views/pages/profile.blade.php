@extends('layouts.app')

@section('title', 'My profile - FoodMart')

@section('content')
<x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Profile', 'url' => null]]" />

<section class="mt-6 dark-glass-card rounded-3xl p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div class="flex items-center gap-5">
        <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-primary to-accent-lime flex items-center justify-center font-display font-black text-3xl text-black shadow-[0_0_24px_rgba(0,245,155,0.3)]">
            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="font-display font-extrabold text-2xl sm:text-3xl text-text-crisp">{{ $user->name }}</h1>
            <p class="text-sm text-text-muted">{{ $user->email }} &middot; member since {{ $user->created_at->format('M Y') }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        @if ($user->is_admin)
            <a href="{{ url('/admin') }}" class="rounded-full border border-border-glass px-5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-text-muted hover:text-primary hover:border-primary/40 transition-colors">Admin panel</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-full border border-red-400/40 px-5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-red-300 hover:bg-red-500/10 transition-colors">Sign out</button>
        </form>
    </div>
</section>

<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
    <x-profile-stat label="Orders" icon="inventory_2" :value="$stats['orders']" sub="All time" />
    <x-profile-stat label="Total spent" icon="payments" icon-class="text-accent-electric-lime" :value="'$'.$stats['spent']" sub="Without cancelled orders" />
    <x-profile-stat label="Saved items" icon="favorite" :value="$stats['saved']" sub="In your favorites" />
</div>

<div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <section class="lg:col-span-7 dark-glass-card rounded-3xl p-6">
        <h2 class="font-display font-bold text-xl text-text-crisp">My orders</h2>

        @if ($orders->isNotEmpty())
            <div class="mt-2 divide-y divide-border-glass">
                @foreach ($orders as $order)
                    <x-profile-order :order="$order" />
                @endforeach
            </div>
            {{ $orders->links() }}
        @else
            <p class="mt-4 text-sm text-text-muted">No orders yet. <a href="{{ route('catalog.index') }}" class="font-bold text-primary hover:underline">Start shopping</a></p>
        @endif
    </section>

    <div class="lg:col-span-5 space-y-6">
        <section class="dark-glass-card rounded-3xl p-6">
            <h2 class="font-display font-bold text-xl text-text-crisp mb-5">Profile details</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-field name="name" label="Full name" :value="$user->name" autocomplete="name" required />
                <x-field name="email" label="Email" type="email" :value="$user->email" autocomplete="email" required />
                <x-field name="phone" label="Phone" type="tel" :value="$user->phone" autocomplete="tel" />
                <x-field name="address" label="Default address" :value="$user->address" autocomplete="street-address" />
                <button type="submit" class="water-ripple-target rounded-full bg-primary hover:bg-emerald-400 px-6 py-2.5 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Save changes</button>
            </form>
        </section>

        <section class="dark-glass-card rounded-3xl p-6">
            <h2 class="font-display font-bold text-xl text-text-crisp mb-5">Change password</h2>
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-field name="current_password" label="Current password" type="password" autocomplete="current-password" required />
                <x-field name="password" label="New password" type="password" autocomplete="new-password" required />
                <x-field name="password_confirmation" label="Repeat new password" type="password" autocomplete="new-password" required />
                <button type="submit" class="water-ripple-target rounded-full border border-primary/40 bg-primary/10 hover:bg-primary hover:text-black px-6 py-2.5 font-display text-xs font-bold uppercase tracking-wider text-primary transition-colors">Update password</button>
            </form>
        </section>
    </div>
</div>
@endsection
