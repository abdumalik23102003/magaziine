{{-- Floating toast: filled by public/js/pages/product.js --}}
<div class="fixed bottom-6 right-6 z-50 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none" id="cart-toast">
    <div class="bg-surface-elevated/95 backdrop-blur-2xl p-space-md rounded-xl shadow-2xl flex items-center gap-space-md min-w-[320px]">
        <div class="w-10 h-10 rounded-full bg-accent-hyper-emerald/20 text-accent-hyper-emerald flex items-center justify-center"><span class="material-symbols-outlined">ac_unit</span></div>
        <div class="flex-1">
            <div class="font-label-md text-label-md text-text-crisp uppercase font-bold" id="toast-title">Added to Cold-Chain Dispatch</div>
            <div class="font-body-sm text-body-sm text-on-surface-variant" id="toast-subtitle"></div>
        </div>
        <span class="font-mono text-accent-hyper-emerald font-bold" id="toast-price"></span>
    </div>
</div>
