@extends('tma.layout')

@section('title', 'Orders')
@section('back', route('tma.home'))

@section('content')
<h1>My orders</h1>

@forelse ($orders as $order)
    <a href="{{ route('tma.orders.show', $order) }}" class="order">
        <div class="row">
            <b>{{ $order->number }}</b>
            <span class="status status-{{ $order->status->value }}">{{ $order->status->label() }}</span>
        </div>
        <div class="row muted small">
            <span>{{ $order->created_at->format('d M Y, H:i') }} &middot; {{ $order->items_count }} {{ $order->items_count === 1 ? 'item' : 'items' }}</span>
            <span>${{ $order->total_price }}</span>
        </div>
    </a>
@empty
    <div class="center">
        <p>No orders yet.</p>
        <a href="{{ route('tma.home') }}" class="btn">Start shopping</a>
    </div>
@endforelse

@if ($orders->hasPages())
    <div class="pager">
        @if ($orders->previousPageUrl())
            <a href="{{ $orders->previousPageUrl() }}" class="btn btn-ghost">Newer</a>
        @else
            <span></span>
        @endif
        @if ($orders->nextPageUrl())
            <a href="{{ $orders->nextPageUrl() }}" class="btn btn-ghost">Older</a>
        @endif
    </div>
@endif
@endsection
