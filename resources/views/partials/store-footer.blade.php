@php
    $features = [
    ['ac_unit', 'text-accent-hyper-emerald', '4°C Cold-Chain Verified', 'Unbroken thermal monitoring from biodynamic harvest to doorstep.'],
    ['verified', 'text-accent-hyper-emerald', 'Zero Chemical Policy', 'Laboratory mass-spectrometry verified free of synthetic residues.'],
    ['bolt', 'text-accent-electric-lime', 'Sub-2hr Metro Dispatch', 'Autonomous electric refrigeration fleet with biological sealing.'],
    ['support_agent', 'text-accent-hyper-emerald', 'Biological Sommelier 24/7', 'Nutritional architects on-call for harvest optimization and regimens.'],
];
    $footerCols = ['Inventory' => ['Living Botanicals', 'Raw Cold-Pressed', 'Demeter Biodynamics', 'Artisanal Cultures'], 'Verification' => ['Mass Spectrometry Logs', 'Thermal Telemetry', 'Soil Microbiome Data', 'Farm Origin Ledger'], 'Client Services' => ['Concierge Dispatch', 'Regimen Subscriptions', 'Cellar & Vault Storage', 'Private Harvest Allocation']];
@endphp
<footer class="w-full bg-surface-carbon mt-space-xl">
    <div class="max-w-[1600px] mx-auto px-margin-mobile md:px-margin py-space-xl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-gutter mb-space-xl pb-space-lg">
            @foreach ($features as [$icon, $color, $title, $text])
                <div class="flex items-start gap-space-md bg-surface-slate/60 p-space-md rounded-xl">
                    <span class="material-symbols-outlined {{ $color }} text-[28px]">{{ $icon }}</span>
                    <div>
                        <div class="font-title text-title text-text-crisp">{{ $title }}</div>
                        <div class="font-body-sm text-body-sm text-text-muted mt-space-xs">{{ $text }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-gutter text-on-surface-variant mb-space-xl">
            <div class="col-span-2 md:col-span-2">
                <div class="flex items-center gap-space-xs mb-space-sm">
                    <span class="font-headline-sm text-headline-sm uppercase text-text-crisp font-semibold tracking-tight">FoodMart</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-accent-hyper-emerald"></span>
                </div>
                <p class="font-body-md text-body-md text-text-muted max-w-sm mb-space-md">Precision biological nutrition and organic epicurean provisioning engineered for physiological optimization.</p>
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-hyper-emerald">Computational Living • Pure Organic</span>
            </div>
            @foreach ($footerCols as $heading => $links)
                <div>
                    <div class="font-label-lg text-label-lg uppercase tracking-wider text-text-crisp mb-space-md">{{ $heading }}</div>
                    <ul class="space-y-space-xs font-body-sm text-body-sm">
                        @foreach ($links as $link)
                            <li class="hover:text-accent-hyper-emerald transition-colors"><a href="#">{{ $link }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between pt-space-lg text-text-muted font-body-sm text-body-sm">
            <p>© {{ date('Y') }} FoodMart Organics Inc. Thermal biological integrity guaranteed.</p>
            <div class="flex gap-space-lg mt-space-sm sm:mt-0">
                <a class="hover:text-on-surface transition-colors" href="#">Privacy Policy</a>
                <a class="hover:text-on-surface transition-colors" href="#">Bio-Security Protocol</a>
                <a class="hover:text-on-surface transition-colors" href="#">Terms of Provisioning</a>
            </div>
        </div>
    </div>
</footer>
