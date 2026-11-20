<div class="bg-surface-carbon p-space-lg rounded-xl shadow-xl flex flex-col gap-space-lg relative overflow-hidden">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-primary-container text-[20px]">bolt</span>
            <h2 class="font-headline-sm text-headline-sm uppercase tracking-tight text-text-crisp">Express Fast Pass</h2>
        </div>
        <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-electric-lime bg-surface-slate px-2.5 py-1 rounded-full">Instant Bioscan Auth</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-space-sm">
        @php $walletBtn = 'group flex items-center justify-center gap-2 py-3 px-space-md rounded-lg bg-surface-slate hover:bg-surface-elevated transition-all shadow-[0_0_15px_-4px_rgba(0,0,0,0.8)] active:scale-[0.98]'; @endphp
        <button class="{{ $walletBtn }}" type="button"><span class="font-headline-sm text-headline-sm tracking-tighter text-text-crisp font-semibold">Pay</span></button>
        <button class="{{ $walletBtn }}" type="button"><span class="font-label-lg text-label-lg tracking-wider text-text-crisp flex items-center gap-1 font-semibold"><span class="text-[#4285F4] font-bold">G</span>Pay</span></button>
        <button class="{{ $walletBtn }}" type="button"><span class="font-label-lg text-label-lg tracking-wider italic text-[#0079C1] font-bold">PayPal</span></button>
    </div>

    <div class="flex items-center gap-space-md my-space-xs">
        <div class="h-[1px] bg-surface-elevated flex-1"></div>
        <span class="font-label-sm text-label-sm uppercase tracking-widest text-text-muted">Or Precision Manual Entry</span>
        <div class="h-[1px] bg-surface-elevated flex-1"></div>
    </div>

    <div class="flex flex-col gap-space-xs">
        <label class="font-label-lg text-label-lg uppercase tracking-wider text-on-surface-variant flex justify-between" for="checkout-email">
            <span>Account Email / Dispatch Notification</span>
            <span class="text-text-muted font-label-sm normal-case tracking-normal">Receipt &amp; Live Telemetry Sent Here</span>
        </label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px]">alternate_email</span>
            <input class="w-full bg-surface-obsidian text-text-crisp pl-11 pr-4 py-3 rounded-lg font-body-md text-body-md focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner" id="checkout-email" placeholder="client.ops@performance-bio.com" type="email" value="alex.vance@neurotech.io"/>
        </div>
        <label class="flex items-center gap-2 mt-2 cursor-pointer select-none">
            <input checked class="w-4 h-4 rounded bg-surface-obsidian text-primary-container accent-primary-container cursor-pointer" type="checkbox"/>
            <span class="font-body-sm text-body-sm text-on-surface-variant">Save credentials for instant biometric 1-click reorder</span>
        </label>
    </div>
</div>
