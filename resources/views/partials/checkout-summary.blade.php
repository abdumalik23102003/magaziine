<div class="lg:col-span-4 sticky top-24 flex flex-col gap-space-lg">
    <div class="bg-surface-carbon/95 backdrop-blur-2xl p-space-lg rounded-xl shadow-[0_8px_32px_rgba(0,0,0,0.8)] relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-primary-container to-transparent opacity-75"></div>

        <div class="flex items-center justify-between pb-space-sm mb-space-sm">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-container text-[20px]">shopping_bag</span>
                <h3 class="font-headline-sm text-headline-sm uppercase text-text-crisp tracking-tight">Order Payload</h3>
            </div>
            <a class="font-label-sm text-label-sm uppercase tracking-wider text-accent-electric-lime hover:underline" href="#">Edit Items ({{ count($orderItems) }})</a>
        </div>

        <div class="flex flex-col gap-space-md max-h-[380px] overflow-y-auto pr-1">
            @foreach ($orderItems as $item)
                <x-checkout-order-item :name="$item['name']" :meta="$item['meta']" :price="$item['price']"
                    :qty="$item['qty']" :discount="$item['discount']" :img="$item['img']" :alt="$item['alt']"/>
            @endforeach
        </div>

        {{-- Promo code --}}
        <div class="my-space-md pt-space-sm">
            <div class="flex items-center gap-2 mb-2">
                <input class="flex-1 bg-surface-obsidian text-text-crisp px-3 py-2 rounded-lg font-label-md text-label-md uppercase tracking-wider focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner" id="promo-input" placeholder="Promo code or gift token" type="text"/>
                <button class="bg-surface-slate hover:bg-surface-elevated text-text-crisp px-4 py-2 rounded-lg font-label-md text-label-md uppercase tracking-wider font-semibold transition-colors" id="promo-apply" type="button">Apply</button>
            </div>
            <div class="flex items-center justify-between bg-surface-slate/80 px-3 py-1.5 rounded-lg" id="promo-pill">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-primary-container text-[16px]">verified</span>
                    <span class="font-mono font-bold text-label-sm text-primary-container tracking-wider">FRESHORGANIC</span>
                    <span class="font-body-sm text-body-sm text-text-muted">(-${{ number_format($voucher, 2) }} Bio-Credit)</span>
                </div>
                <button class="text-text-muted hover:text-error transition-colors flex items-center" id="promo-remove" type="button">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>
        </div>

        {{-- Totals --}}
        <div class="flex flex-col gap-2 pt-space-xs text-body-md font-body-md">
            <div class="flex justify-between items-center text-text-muted"><span>Produce &amp; Provisions Subtotal</span><span class="font-mono text-text-crisp">${{ number_format($subtotal, 2) }}</span></div>
            <div class="flex justify-between items-center text-primary-container" id="voucher-row"><span>Voucher Offset (FRESHORGANIC)</span><span class="font-mono font-semibold">-${{ number_format($voucher, 2) }}</span></div>
            <div class="flex justify-between items-center text-text-muted"><span>State &amp; Local Botanical Tax</span><span class="font-mono text-text-crisp">${{ number_format($tax, 2) }}</span></div>
            <div class="flex justify-between items-center text-text-muted">
                <div class="flex items-center gap-1">
                    <span>Cold-Chain Express Delivery</span>
                    <span class="material-symbols-outlined text-[14px] text-accent-electric-lime" title="Unlocked over $20 threshold">check_circle</span>
                </div>
                <div class="flex items-center gap-1.5"><span class="line-through text-text-muted font-mono text-body-sm">$3.99</span><span class="font-mono text-accent-electric-lime font-bold">FREE</span></div>
            </div>
            <div class="hidden justify-between items-center text-tertiary-fixed" id="roundup-row"><span>Soil Regeneration Round-up</span><span class="font-mono font-semibold">+${{ number_format($roundup, 2) }}</span></div>

            <div class="flex justify-between items-baseline pt-space-sm mt-space-xs">
                <div class="flex flex-col">
                    <span class="font-label-lg text-label-lg uppercase tracking-wider text-text-crisp font-bold">Total Due</span>
                    <span class="font-body-sm text-body-sm text-text-muted">Instant Authorization</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="font-headline-lg text-headline-lg font-bold font-mono text-primary-container tracking-tight" id="sidebar-total">${{ number_format($total, 2) }}</span>
                    <span class="font-label-sm text-label-sm text-text-muted font-mono">USD</span>
                </div>
            </div>
        </div>

        <div class="mt-space-lg pt-space-md flex flex-col gap-2.5">
            <div class="flex items-center gap-2 text-text-muted"><span class="material-symbols-outlined text-primary-container text-[18px]">severe_cold</span><span class="font-body-sm text-body-sm"><strong class="text-text-crisp font-semibold">0-4°C Guarantee:</strong> Real-time temperature sensors in every vacuum-sealed crate.</span></div>
            <div class="flex items-center gap-2 text-text-muted"><span class="material-symbols-outlined text-accent-electric-lime text-[18px]">published_with_changes</span><span class="font-body-sm text-body-sm"><strong class="text-text-crisp font-semibold">Instant Bio-Refunds:</strong> Imperfect fruit? Credit issued in 30 seconds via app.</span></div>
            <div class="flex items-center gap-2 text-text-muted"><span class="material-symbols-outlined text-tertiary-fixed text-[18px]">verified</span><span class="font-body-sm text-body-sm"><strong class="text-text-crisp font-semibold">Verified Organic:</strong> 100% Demeter biodynamic or USDA organic partner farms.</span></div>
        </div>
    </div>

    {{-- Live telemetry pill --}}
    <div class="bg-surface-slate/60 p-space-md rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-space-sm">
            <div class="relative flex items-center justify-center">
                <span class="w-3 h-3 rounded-full bg-primary-container"></span>
                <span class="w-3 h-3 rounded-full bg-primary-container animate-ping absolute"></span>
            </div>
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-primary-container font-semibold">Bay Node 04 Transmit</span>
                <span class="font-body-sm text-body-sm text-text-crisp">3 Cold Couriers Available in District</span>
            </div>
        </div>
        <span class="font-mono text-label-lg font-bold text-accent-electric-lime">3.2°C Ambient</span>
    </div>
</div>
