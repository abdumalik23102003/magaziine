<x-profile-card class="p-space-md md:p-space-lg relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-accent-hyper-emerald/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-md border-b border-surface-container-high/40">
        <div>
            <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-hyper-emerald">Real-Time Biological In-Transit</span>
            <h2 class="font-headline-sm text-headline-sm text-text-crisp font-semibold tracking-tight mt-0.5">LIVE COLD-CHAIN DISPATCH #FM-88290</h2>
        </div>
        <div class="flex items-center gap-space-xs bg-surface-slate px-space-md py-1.5 rounded-full shrink-0 shadow-sm">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-hyper-emerald opacity-90"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-accent-hyper-emerald shadow-[0_0_8px_#00f59b]"></span>
            </span>
            <span class="font-label-sm text-label-sm uppercase tracking-widest text-text-crisp font-semibold">EN ROUTE • CRYO-POD 04</span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-gutter my-space-md">
        <div class="bg-surface-slate/70 p-space-md rounded-xl flex flex-col justify-between">
            <div class="flex items-center justify-between text-text-muted"><span class="font-label-sm text-label-sm uppercase tracking-wider">Core Temperature</span><span class="material-symbols-outlined text-accent-hyper-emerald text-[18px]">ac_unit</span></div>
            <div class="mt-2">
                <div class="font-headline-sm text-headline-sm font-semibold text-accent-hyper-emerald tracking-tight">2.8°C</div>
                <div class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Target: 0.0°C – 4.0°C (Unbroken)</div>
            </div>
            <div class="mt-3 pt-2 border-t border-surface-container-high/40 flex items-center justify-between">
                <svg class="w-full h-6 text-accent-hyper-emerald" fill="none" viewBox="0 0 160 24">
                    <path d="M0 16 Q20 14 40 15 T80 12 T120 13 T160 10" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"></path>
                    <circle class="animate-pulse" cx="160" cy="10" fill="#00f59b" r="3"></circle>
                </svg>
            </div>
        </div>
        <div class="bg-surface-slate/70 p-space-md rounded-xl flex flex-col justify-between">
            <div class="flex items-center justify-between text-text-muted"><span class="font-label-sm text-label-sm uppercase tracking-wider">Autonomous ETA</span><span class="material-symbols-outlined text-accent-electric-lime text-[18px]">timelapse</span></div>
            <div class="mt-2">
                <div class="font-headline-sm text-headline-sm font-semibold text-text-crisp tracking-tight">24 mins</div>
                <div class="font-body-sm text-body-sm text-text-muted mt-0.5">Estimated 11:42 AM PST</div>
            </div>
            <div class="mt-3 pt-2 border-t border-surface-container-high/40 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><span class="material-symbols-outlined text-accent-hyper-emerald text-[14px]">electric_bolt</span><span>Direct Route via Embarcadero</span></div>
        </div>
        <div class="bg-surface-slate/70 p-space-md rounded-xl flex flex-col justify-between">
            <div class="flex items-center justify-between text-text-muted"><span class="font-label-sm text-label-sm uppercase tracking-wider">Dedicated Courier</span><span class="material-symbols-outlined text-outline text-[18px]">badge</span></div>
            <div class="mt-2">
                <div class="font-title text-title font-semibold text-text-crisp">Marcus C.</div>
                <div class="font-body-sm text-body-sm text-text-muted mt-0.5">Cold-Certified Specialist</div>
            </div>
            <div class="mt-3 pt-2 border-t border-surface-container-high/40 flex items-center gap-1.5 font-label-sm text-label-sm text-accent-hyper-emerald"><span class="material-symbols-outlined text-[14px]">electric_car</span><span>EV Climate Pod #18</span></div>
        </div>
    </div>

    <div class="mt-space-md">
        <div class="font-label-sm text-label-sm uppercase tracking-wider text-text-muted mb-space-sm flex items-center justify-between">
            <span>Biological Harvest Payload ({{ count($payload) }} Items Inspected)</span>
            <span class="text-accent-hyper-emerald">Sensors Calibrated</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-gutter-sm">
            @foreach ($payload as $p)
                <x-payload-item :name="$p['name']" :meta="$p['meta']" :img="$p['img']" :alt="$p['alt']"/>
            @endforeach
        </div>
    </div>

    <div class="mt-space-md pt-space-md border-t border-surface-container-high/40 flex flex-wrap items-center justify-between gap-space-sm">
        <div class="flex items-center gap-2 text-on-surface-variant font-body-sm text-body-sm"><span class="material-symbols-outlined text-accent-hyper-emerald text-[16px]">sensors</span><span>Cryo-Telemetry Stream Active • 100% Thermal Integrity Sealed</span></div>
        <div class="flex items-center gap-space-sm">
            <button class="flex items-center gap-1.5 font-label-md text-label-md uppercase tracking-wider bg-surface-slate hover:bg-surface-elevated text-text-crisp px-space-md py-space-xs rounded-lg transition-colors"><span class="material-symbols-outlined text-[16px]">chat</span><span>Courier Guidance</span></button>
            <button class="flex items-center gap-1.5 font-label-md text-label-md uppercase tracking-wider bg-accent-hyper-emerald text-surface-obsidian font-semibold px-space-md py-space-xs rounded-lg hover:shadow-[0_0_20px_rgba(0,245,155,0.4)] transition-all"><span class="material-symbols-outlined text-[16px]">navigation</span><span>Live GPS Telemetry</span></button>
        </div>
    </div>
</x-profile-card>
