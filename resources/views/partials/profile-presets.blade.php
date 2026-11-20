<x-profile-card class="p-space-md md:p-space-lg">
    <div class="flex items-center justify-between pb-space-md border-b border-surface-container-high/40">
        <div class="flex items-center gap-space-sm">
            <span class="material-symbols-outlined text-accent-hyper-emerald text-[24px]">home_pin</span>
            <div>
                <h2 class="font-title text-title text-text-crisp font-semibold tracking-tight">Delivery Presets &amp; Intake Protocol</h2>
                <div class="font-body-sm text-body-sm text-text-muted">Precision handover guidelines for autonomous &amp; human couriers</div>
            </div>
        </div>
        <button class="font-label-sm text-label-sm uppercase tracking-wider bg-surface-slate hover:bg-surface-elevated text-text-crisp px-3 py-1 rounded transition-colors">Edit</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter mt-space-md">
        <div class="bg-surface-slate/70 p-space-md rounded-xl flex flex-col justify-between">
            <div>
                <div class="font-label-sm text-label-sm uppercase tracking-wider text-text-muted mb-1 flex items-center justify-between"><span>Default Node Destination</span><span class="text-accent-hyper-emerald text-[11px] font-semibold">PRIMARY</span></div>
                <div class="font-title text-title text-text-crisp font-semibold mt-1">{{ $address['street'] }}</div>
                <div class="font-body-md text-body-md text-on-surface-variant">{{ $address['suite'] }}</div>
                <div class="font-body-sm text-body-sm text-text-muted mt-0.5">{{ $address['city'] }}</div>
            </div>
            <div class="mt-space-md pt-2 border-t border-surface-container-high/40 flex items-center gap-2 font-label-sm text-label-sm text-text-muted"><span class="material-symbols-outlined text-outline text-[16px]">meeting_room</span><span>Access Protocol: Concierge Keycard Node #04</span></div>
        </div>
        <div class="bg-surface-slate/70 p-space-md rounded-xl flex flex-col justify-between">
            <div>
                <div class="font-label-sm text-label-sm uppercase tracking-wider text-text-muted mb-1">Courier Intake Protocol</div>
                <p class="font-body-sm text-body-sm text-text-crisp italic mt-1 leading-relaxed">“{{ $address['note'] }}”</p>
            </div>
            <div class="mt-space-md pt-2 border-t border-surface-container-high/40 flex items-center gap-2 font-label-sm text-label-sm text-accent-hyper-emerald"><span class="material-symbols-outlined text-[16px]">lock_reset</span><span>Tamper-Sealed Thermal Handover Active</span></div>
        </div>
    </div>

    <div class="mt-space-md pt-space-md border-t border-surface-container-high/40 grid grid-cols-1 sm:grid-cols-2 gap-space-md">
        <div class="flex items-center gap-space-sm bg-surface-slate/50 p-space-sm rounded-lg">
            <div class="w-10 h-10 rounded-lg bg-surface-obsidian flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-accent-electric-lime text-[22px]">speed</span></div>
            <div>
                <div class="font-label-sm text-label-sm uppercase text-text-muted tracking-wider">Velocity Protocol</div>
                <div class="font-label-md text-label-md text-text-crisp font-semibold">Racing Precision Express (Climate EV)</div>
            </div>
        </div>
        <div class="flex items-center justify-between bg-surface-slate/50 p-space-sm rounded-lg">
            <div class="flex items-center gap-space-sm">
                <div class="w-10 h-10 rounded-lg bg-surface-obsidian flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-accent-hyper-emerald text-[22px]">autorenew</span></div>
                <div>
                    <div class="font-label-sm text-label-sm uppercase text-text-muted tracking-wider">Weekly Replenishment</div>
                    <div class="font-label-md text-label-md text-text-crisp font-semibold">Next: Thursday 09:00 AM</div>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input checked class="sr-only peer" type="checkbox"/>
                <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-surface-obsidian after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-text-crisp after:border-surface-variant after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent-hyper-emerald"></div>
            </label>
        </div>
    </div>
</x-profile-card>
