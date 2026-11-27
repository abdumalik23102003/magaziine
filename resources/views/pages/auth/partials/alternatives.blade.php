@php
    $socialProviders = app(\App\Services\SocialAuthService::class)->enabled();
    $telegramBot = filled(config('telegram.bot_token')) && filled(config('telegram.bot_username'))
        ? ltrim((string) config('telegram.bot_username'), '@')
        : null;
    $altButton = 'flex w-full items-center justify-center rounded-full border border-border-glass hover:border-primary/60 py-3 font-display text-xs font-bold uppercase tracking-wider text-text-crisp transition-colors';
@endphp

<div class="mt-8 flex items-center gap-3 text-[10px] font-mono font-bold uppercase tracking-widest text-text-muted">
    <span class="h-px flex-1 bg-border-glass"></span>
    <span>or continue with</span>
    <span class="h-px flex-1 bg-border-glass"></span>
</div>

<div class="mt-5 space-y-3">
    <a href="{{ route('login.code') }}" class="{{ $altButton }}">Email code</a>

    @foreach ($socialProviders as $provider => $label)
        <a href="{{ route('auth.social.redirect', $provider) }}" class="{{ $altButton }}">{{ $label }}</a>
    @endforeach

    @if ($telegramBot)
        <div class="flex justify-center pt-1">
            <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ $telegramBot }}" data-size="large" data-radius="999" data-auth-url="{{ route('auth.telegram.callback') }}"></script>
        </div>
    @endif
</div>
