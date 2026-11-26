<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="tg-user-id" content="{{ auth()->user()?->telegram_id }}">
    <title>@yield('title', 'FoodMart')</title>
    <script src="https://telegram.org/js/telegram-web-app.js?64"></script>
    <link rel="stylesheet" href="{{ asset('css/tma.css') }}">
</head>
<body data-login="{{ route('tma.login') }}" data-back="@yield('back')" @hasSection('bare') data-bare @endif>
    <main class="page">
        @if (session('status'))
            <p class="note" role="status">{{ session('status') }}</p>
        @endif
        @foreach ($errors->all() as $error)
            <p class="note note-error" role="alert">{{ $error }}</p>
        @endforeach

        @yield('content')
    </main>

    <div id="tma-toast" class="toast" role="status" hidden></div>

    @unless (View::hasSection('bare'))
        @includeIf('tma.nav')
    @endunless

    <script src="{{ asset('js/pages/tma.js') }}"></script>
    @stack('scripts')
</body>
</html>
