@extends('tma.layout')

@section('bare', '1')

@section('content')
<div id="tma-login" class="center" data-url="{{ route('tma.auth') }}">
    <h1>FoodMart</h1>
    <p id="tma-status" class="muted" role="status">Signing you in...</p>
    <button type="button" id="tma-retry" class="btn" hidden>Try again</button>
    <noscript><p>JavaScript is required.</p></noscript>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/tma-login.js') }}"></script>
@endpush
