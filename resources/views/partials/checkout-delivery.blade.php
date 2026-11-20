<div class="bg-surface-carbon p-space-lg rounded-xl shadow-xl flex flex-col gap-space-lg">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-primary-container text-[20px]">local_shipping</span>
            <h2 class="font-headline-sm text-headline-sm uppercase tracking-tight text-text-crisp">Cold-Chain Intake Destination</h2>
        </div>
        <div class="flex items-center gap-1.5 text-accent-electric-lime font-label-sm text-label-sm uppercase tracking-wider">
            <span class="material-symbols-outlined text-[14px]">ac_unit</span>
            <span>Cryo-Carrier Active</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
        <x-checkout-field label="Intake Officer / Name" value="Alexander Vance"/>
        <x-checkout-field label="Mobile Contact (Courier SMS Telemetry)" type="tel" value="+1 (415) 890-4421"/>
        <x-checkout-field class="md:col-span-2" label="Street Address" icon="location_on" value="742 Montgomery St, Floor 14"/>
        <x-checkout-field label="Apt / Penthouse / Suite" value="Residence Suite 14B"/>
        <div class="grid grid-cols-3 gap-space-xs">
            <x-checkout-field size="sm" label="City" value="San Francisco"/>
            <x-checkout-field size="sm" label="State" value="CA"/>
            <x-checkout-field size="sm" label="ZIP" value="94111"/>
        </div>
    </div>

    <div class="flex flex-col gap-space-sm pt-space-xs">
        <span class="font-label-lg text-label-lg uppercase tracking-wider text-on-surface-variant">Dispatch Velocity Protocol</span>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-sm" id="speed-selector">
            <x-speed-option active name="Racing Precision Express" tag="Priority 01"
                text="Direct cold-vault dispatch within 30-45 mins via climate-sealed EV pod."
                badge="FREE" badgeClass="text-accent-electric-lime font-bold" strike="$3.99"/>
            <x-speed-option name="Eco-Optimized Evening Slot" tag="Batch Slot"
                text="7:00 PM – 9:00 PM tonight. Clustered routing for minimal regional CO2 footprint."
                badge="Included" extra="0.0 kg CO2" extraClass="text-primary-container"/>
            <x-speed-option name="Tomorrow Morning Fresh Harvest" tag="Dawn Cycle"
                text="8:00 AM – 10:00 AM tomorrow. Cut at 4:30 AM from Sonoma micro-nodes."
                badge="Included" extra="Peak Brix" extraClass="text-tertiary-fixed"/>
        </div>
    </div>

    <div class="flex flex-col gap-1.5 pt-space-xs">
        <label class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant flex items-center justify-between" for="driver-notes">
            <span>Intake Courier Guidance &amp; Cryo-Placement</span>
            <span class="text-text-muted font-label-sm normal-case">Optional Protocol</span>
        </label>
        <textarea class="w-full bg-surface-obsidian text-text-crisp p-3 rounded-lg font-body-md text-body-md focus:outline-none focus:ring-1 focus:ring-primary-container shadow-inner resize-none" id="driver-notes" rows="2">Leave with 14th floor concierge or place directly inside thermal insulated mycelium cold tote bag at apartment doorway. Ring unit chime once.</textarea>
    </div>
</div>
