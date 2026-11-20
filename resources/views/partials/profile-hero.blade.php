<section class="relative bg-surface-carbon/90 backdrop-blur-2xl rounded-xl p-space-md md:p-space-lg shadow-xl overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-accent-hyper-emerald/60 to-transparent"></div>
    <div class="flex flex-col gap-space-md">

        <div class="flex flex-wrap items-center justify-between gap-space-sm">
            <div class="flex items-center gap-space-xs bg-surface-slate px-space-md py-1 rounded-full shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-hyper-emerald opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-accent-hyper-emerald"></span>
                </span>
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-hyper-emerald font-semibold">PRODUCE PROTOCOL ELITE MEMBER • TIER 03</span>
            </div>
            <div class="flex items-center gap-space-md font-label-md text-label-md text-text-muted">
                <div class="flex items-center gap-1.5"><span class="material-symbols-outlined text-outline text-[16px]">fingerprint</span><span>Bio-Key ID: <strong class="text-text-crisp font-semibold">{{ $user['bio_key'] }}</strong></span></div>
                <span class="hidden sm:inline text-surface-variant">•</span>
                <div class="hidden sm:flex items-center gap-1.5"><span class="material-symbols-outlined text-outline text-[16px]">calendar_today</span><span>Member Since: <strong class="text-text-crisp font-semibold">{{ $user['since'] }}</strong></span></div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pt-space-xs">
            <div class="flex items-center gap-space-md">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-full p-1 bg-gradient-to-tr from-accent-hyper-emerald via-accent-electric-lime/40 to-transparent shadow-[0_0_24px_rgba(0,245,155,0.3)]">
                        <div class="w-full h-full rounded-full overflow-hidden bg-surface-slate flex items-center justify-center">
                            <img class="w-full h-full object-cover" alt="{{ $user['name'] }}" src="{{ $user['avatar'] }}"/>
                        </div>
                    </div>
                    <div class="absolute bottom-0 right-0 bg-surface-obsidian p-1 rounded-full">
                        <div class="w-4 h-4 rounded-full bg-accent-hyper-emerald flex items-center justify-center" title="Verified Biological Identity">
                            <span class="material-symbols-outlined text-on-primary text-[10px]">check</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-space-xs flex-wrap">
                        <h1 class="font-headline-md text-headline-md text-text-crisp font-semibold tracking-tight">{{ $user['name'] }}</h1>
                        <span class="material-symbols-outlined text-accent-hyper-emerald text-[20px]" title="Cold-Chain Certified Consignee">verified</span>
                    </div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-2 mt-0.5">
                        <span class="text-accent-hyper-emerald">{{ $user['handle'] }}</span><span>•</span><span>{{ $user['node'] }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="inline-flex items-center gap-1 font-label-sm text-label-sm uppercase px-2 py-0.5 rounded bg-surface-slate text-text-muted"><span class="material-symbols-outlined text-accent-electric-lime text-[13px]">bolt</span> Sub-2HR Priority Fleet</span>
                        <span class="inline-flex items-center gap-1 font-label-sm text-label-sm uppercase px-2 py-0.5 rounded bg-surface-slate text-text-muted"><span class="material-symbols-outlined text-accent-hyper-emerald text-[13px]">ac_unit</span> Live Cryo-Telemetry</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-space-sm flex-wrap self-start lg:self-center">
                <button class="flex items-center gap-space-xs bg-surface-slate hover:bg-surface-elevated text-text-crisp hover:text-accent-hyper-emerald font-label-lg text-label-lg uppercase tracking-wider px-space-md py-space-xs rounded-lg transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">edit_note</span><span>Edit Profile Info</span>
                </button>
                <button class="flex items-center gap-space-xs bg-accent-hyper-emerald text-surface-obsidian hover:shadow-[0_0_24px_rgba(0,245,155,0.45)] hover:scale-[1.01] font-label-lg text-label-lg uppercase tracking-wider px-space-md py-space-xs rounded-lg transition-all font-semibold">
                    <span class="material-symbols-outlined text-[18px]">lab_profile</span><span>Download Telemetry Report</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter mt-space-sm">
            <x-profile-stat label="Harvests Delivered" icon="inventory_2" :value="$user['orders'] . ' Orders'" sub-icon="format_image_left" sub="100% On-Time Cold-Chain" sub-class="text-accent-hyper-emerald"/>
            <x-profile-stat label="Carbon Offset Credit" icon="eco" icon-class="text-accent-electric-lime" value="142.8 kg CO₂e" sub-icon="compost" sub="Regenerative Soil Rebuilt" sub-class="text-on-surface-variant"/>
            <x-profile-stat label="Active Bio-Credits" icon="token" :value="'$' . $user['credits']" sub="Ready for Next Dispatch">
                <button class="font-label-sm text-label-sm uppercase tracking-wider bg-accent-hyper-emerald/20 hover:bg-accent-hyper-emerald text-accent-hyper-emerald hover:text-surface-obsidian font-semibold px-2.5 py-1 rounded transition-colors">Redeem</button>
            </x-profile-stat>
            <x-profile-stat label="Freshness Quotient" icon="vital_signs" icon-class="text-tertiary-fixed" value="99.4%" sub-icon="science" sub="Peak Optical Brix Rating" sub-class="text-tertiary-fixed"/>
        </div>
    </div>
</section>
