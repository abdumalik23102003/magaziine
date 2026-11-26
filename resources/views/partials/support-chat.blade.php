@php
    $supportCfg = [
        'csrf' => csrf_token(),
        'guest' => ! auth()->check(),
        'maxLength' => (int) config('support.max_length'),
        'pollOpen' => (int) config('support.polling.open'),
        'pollClosed' => (int) config('support.polling.closed'),
        'urls' => [
            'show' => route('support.chat.show'),
            'messages' => route('support.chat.messages'),
            'store' => route('support.chat.store'),
            'close' => route('support.chat.close'),
        ],
    ];
@endphp

<div id="support-chat" data-config="{{ json_encode($supportCfg) }}">
    <button type="button" data-launcher aria-label="Support chat"
        class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full border border-fuchsia-500/60 bg-zinc-950 text-fuchsia-300 shadow-[0_0_20px_rgba(217,70,239,.45)] transition hover:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14Z"/></svg>
        <span data-badge class="absolute -right-1 -top-1 hidden min-w-5 rounded-full bg-fuchsia-500 px-1.5 text-center text-xs font-bold text-white">0</span>
    </button>

    <section data-panel role="dialog" aria-label="Support chat"
        class="fixed bottom-24 right-5 z-50 hidden h-[28rem] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl border border-fuchsia-500/50 bg-zinc-950 text-zinc-100 shadow-[0_0_32px_rgba(217,70,239,.35)]">
        <header class="flex items-center justify-between border-b border-fuchsia-500/30 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-fuchsia-300">Yordam markazi</p>
                <p data-offline class="hidden text-xs text-zinc-400">{{ config('support.offline_message') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-end class="text-xs text-zinc-400 underline hover:text-red-400">Suhbatni yopish</button>
                <button type="button" data-hide aria-label="Yashirish" class="text-xl leading-none text-zinc-400 hover:text-white">&times;</button>
            </div>
        </header>

        <div data-list class="flex-1 space-y-2 overflow-y-auto px-3 py-3"></div>

        <p data-note class="hidden px-4 pb-1 text-xs text-zinc-400"></p>

        <form data-form class="space-y-2 border-t border-fuchsia-500/30 p-3">
            <div data-name-wrap class="hidden">
                <input data-name type="text" maxlength="60" placeholder="Ismingiz (ixtiyoriy)"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-100 focus:border-fuchsia-500 focus:outline-none">
            </div>
            <div class="flex gap-2">
                <input data-input type="text" maxlength="{{ (int) config('support.max_length') }}" autocomplete="off" placeholder="Xabar yozing..."
                    class="min-w-0 flex-1 rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 focus:border-fuchsia-500 focus:outline-none">
                <button type="submit" class="rounded-lg bg-fuchsia-600 px-4 text-sm font-semibold text-white hover:bg-fuchsia-500">Yuborish</button>
            </div>
        </form>
    </section>

    <template data-tpl="customer">
        <div class="ml-auto max-w-[80%]">
            <div data-body class="whitespace-pre-wrap break-words rounded-2xl rounded-br-sm bg-fuchsia-600/90 px-3 py-2 text-sm text-white"></div>
            <div data-state class="mt-1 hidden text-right text-xs text-red-400">Yuborilmadi <button type="button" data-retry class="underline">Qayta urinish</button></div>
        </div>
    </template>
    <template data-tpl="operator">
        <div class="mr-auto max-w-[80%]">
            <div data-body class="whitespace-pre-wrap break-words rounded-2xl rounded-bl-sm border border-cyan-500/40 bg-zinc-900 px-3 py-2 text-sm text-cyan-100"></div>
        </div>
    </template>
    <template data-tpl="system">
        <div class="mx-auto max-w-[90%]">
            <div data-body class="whitespace-pre-wrap break-words rounded-xl bg-zinc-800 px-3 py-1.5 text-center text-xs text-zinc-300"></div>
        </div>
    </template>
</div>

<script src="{{ asset('js/support-chat.js') }}" defer></script>
