<div class="bg-surface-carbon p-space-lg rounded-xl shadow-xl flex flex-col gap-space-lg">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-primary-container text-[20px]">credit_card</span>
            <h2 class="font-headline-sm text-headline-sm uppercase tracking-tight text-text-crisp">Payment Security Gateway</h2>
        </div>
        <div class="flex items-center gap-2">
            <span class="font-label-sm text-label-sm text-text-muted uppercase tracking-widest">End-to-End HSM Tokenized</span>
            <span class="material-symbols-outlined text-primary-container text-[18px]">verified</span>
        </div>
    </div>

    <div class="bg-surface-slate/80 p-space-md rounded-xl shadow-inner flex flex-col gap-space-md">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-space-xs">
                <span class="material-symbols-outlined text-accent-electric-lime text-[22px]">contactless</span>
                <span class="font-label-lg text-label-lg uppercase tracking-wider text-text-crisp font-semibold ml-2">Credit / Debit Card</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded bg-surface-obsidian font-label-sm text-label-sm font-bold text-text-crisp">VISA</span>
                <span class="px-2 py-0.5 rounded bg-surface-obsidian font-label-sm text-label-sm font-bold text-[#EB001B]">MC</span>
                <span class="px-2 py-0.5 rounded bg-surface-obsidian font-label-sm text-label-sm font-bold text-[#006FCF]">AMEX</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <div class="md:col-span-2 flex flex-col gap-1.5">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Card Number</label>
                <div class="relative">
                    <input class="w-full bg-surface-obsidian text-text-crisp px-4 py-2.5 rounded-lg font-mono text-body-md focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner tracking-wider" type="text" value="•••• •••• •••• 9012"/>
                    <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-primary-container text-[20px]">format_image_left</span>
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Expiration Date</label>
                <input class="w-full bg-surface-obsidian text-text-crisp px-4 py-2.5 rounded-lg font-mono text-body-md focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner" type="text" value="09 / 28"/>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant flex justify-between">
                    <span>Security CVC</span><span class="text-text-muted font-label-sm">3 Digits</span>
                </label>
                <input class="w-full bg-surface-obsidian text-text-crisp px-4 py-2.5 rounded-lg font-mono text-body-md focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner" maxlength="4" type="password" value="•••"/>
            </div>
            <div class="md:col-span-2 flex flex-col gap-1.5">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Name on Card</label>
                <input class="w-full bg-surface-obsidian text-text-crisp px-4 py-2.5 rounded-lg font-body-md text-body-md uppercase tracking-wider focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner" type="text" value="ALEXANDER VANCE"/>
            </div>
        </div>
    </div>

    {{-- Round-up donation toggle --}}
    <div class="p-space-md rounded-xl bg-surface-obsidian flex items-center justify-between">
        <div class="flex items-center gap-space-sm">
            <div class="w-8 h-8 rounded-lg bg-surface-slate flex items-center justify-center text-accent-electric-lime">
                <span class="material-symbols-outlined text-[20px]">psychiatry</span>
            </div>
            <div class="flex flex-col">
                <span class="font-label-lg text-label-lg uppercase tracking-wider text-text-crisp">Regenerative Soil Micro-Donation</span>
                <span class="font-body-sm text-body-sm text-text-muted">Round up +${{ number_format($roundup, 2) }} for Demeter-certified carbon-negative topsoil restoration</span>
            </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input class="sr-only peer" id="soil-roundup" type="checkbox"/>
            <div class="w-11 h-6 bg-surface-elevated peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-text-crisp after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-container"></div>
        </label>
    </div>

    {{-- Place order --}}
    <div class="pt-space-xs flex flex-col gap-space-sm">
        <button class="group relative w-full overflow-hidden rounded-xl bg-primary-container hover:bg-primary text-on-primary py-4 px-space-lg flex items-center justify-between transition-all duration-300 shadow-[0_0_30px_rgba(0,245,155,0.4)] hover:shadow-[0_0_45px_rgba(0,245,155,0.6)] active:scale-[0.99]" type="button">
            <div class="flex items-center gap-space-xs font-headline-md text-headline-md font-bold uppercase tracking-tight text-on-primary">
                <span class="material-symbols-outlined text-[26px]">lock</span>
                <span>Confirm Dispatch &amp; Place Order</span>
            </div>
            <div class="flex items-center gap-space-sm font-headline-md text-headline-md font-bold font-mono text-on-primary">
                <span id="cta-total-display">${{ number_format($total, 2) }}</span>
                <span class="material-symbols-outlined group-hover:translate-x-1.5 transition-transform text-[24px]">east</span>
            </div>
        </button>
        <div class="flex items-center justify-center gap-space-md text-center text-text-muted font-body-sm text-body-sm">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-primary-container text-[16px]">verified_user</span> 256-Bit Encrypted</span>
            <span>•</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-accent-electric-lime text-[16px]">timer</span> Instant Dispatch Node Sign-off</span>
            <span>•</span>
            <span>Zero Biological Degradation Guarantee</span>
        </div>
    </div>
</div>
