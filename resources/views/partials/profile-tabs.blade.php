@php
    $tabs = [
        ['icon' => 'dashboard',         'label' => 'Overview & Orders'],
        ['icon' => 'thermostat',        'label' => 'Cold-Chain & Delivery Presets'],
        ['icon' => 'lock',              'label' => 'Biometric & Payment Gateway'],
        ['icon' => 'nutrition',         'label' => 'Nutritional & Dietary Profile'],
        ['icon' => 'workspace_premium', 'label' => 'Loyalty & Harvest Perks'],
    ];
@endphp
<nav aria-label="Profile Tabs" id="profile-tabs" class="w-full flex items-center gap-space-xs overflow-x-auto pb-1 border-b border-surface-container-high/40">
    @foreach ($tabs as $i => $tab)
        <button type="button" data-tab="{{ $i }}"
            class="profile-tab flex items-center gap-space-xs font-label-lg text-label-lg uppercase tracking-wider px-space-md py-space-sm rounded-lg shrink-0 transition-colors {{ $i === 0 ? 'bg-surface-slate text-accent-hyper-emerald font-semibold shadow-sm' : 'text-on-surface-variant hover:text-text-crisp hover:bg-surface-slate/50' }}">
            <span class="material-symbols-outlined text-[18px]">{{ $tab['icon'] }}</span>
            <span>{{ $tab['label'] }}</span>
            <span class="tab-dot w-1.5 h-1.5 rounded-full bg-accent-hyper-emerald shadow-[0_0_8px_#00f59b] {{ $i === 0 ? '' : 'hidden' }}"></span>
        </button>
    @endforeach
</nav>
