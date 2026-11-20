<div class="lg:col-span-4 flex flex-col gap-space-lg">

    {{-- Dietary & botanical filters --}}
    <x-profile-card>
        <x-card-header icon="spa" title="Dietary & Botanical Filters">
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-accent-hyper-emerald font-semibold">ACTIVE</span>
        </x-card-header>
        <div class="mt-space-md flex flex-col gap-space-sm">
            <div class="font-label-sm text-label-sm uppercase tracking-wider text-text-muted">Target Standards</div>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($standards as $i => $std)
                    <span class="font-label-sm text-label-sm px-2.5 py-1 rounded-full bg-surface-slate {{ $i === 0 ? 'text-accent-hyper-emerald' : 'text-text-crisp' }} flex items-center gap-1 font-medium shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-hyper-emerald"></span>{{ $std }}
                    </span>
                @endforeach
            </div>
            <div class="font-label-sm text-label-sm uppercase tracking-wider text-text-muted mt-space-xs">Biological Shield Filters</div>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($allergens as $a)
                    <span class="font-label-sm text-label-sm px-2.5 py-1 rounded-full bg-error-container/20 text-error flex items-center gap-1 font-medium">
                        <span class="material-symbols-outlined text-[13px]">block</span>{{ $a }}
                    </span>
                @endforeach
            </div>
            <button class="mt-space-sm w-full py-space-xs rounded-lg bg-surface-slate hover:bg-surface-elevated text-accent-hyper-emerald font-label-sm text-label-sm uppercase tracking-wider font-semibold transition-colors flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">tune</span><span>+ Customize Taste Profile</span>
            </button>
        </div>
    </x-profile-card>

    {{-- Payment tokens --}}
    <x-profile-card>
        <x-card-header icon="credit_card" icon-class="text-outline" title="Payment Tokens">
            <span class="font-label-sm text-label-sm text-text-muted uppercase">PCI DSS 4.0</span>
        </x-card-header>
        <div class="mt-space-md flex flex-col gap-space-sm">
            <div class="bg-surface-slate/80 p-space-sm rounded-lg flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-space-sm">
                    <div class="w-10 h-7 rounded bg-surface-obsidian flex items-center justify-center font-headline-sm text-label-lg font-bold text-text-crisp tracking-wider">VISA</div>
                    <div>
                        <div class="font-label-md text-label-md text-text-crisp font-semibold">Visa Signature •••• 9012</div>
                        <div class="font-body-sm text-body-sm text-text-muted">Exp 09/28</div>
                    </div>
                </div>
                <span class="font-label-sm text-label-sm uppercase tracking-wider px-2 py-0.5 rounded bg-accent-hyper-emerald/20 text-accent-hyper-emerald font-semibold">DEFAULT</span>
            </div>
            <div class="bg-surface-slate/80 p-space-sm rounded-lg flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-space-sm">
                    <div class="w-10 h-7 rounded bg-surface-obsidian flex items-center justify-center text-text-crisp"><span class="material-symbols-outlined text-[18px]">phone_iphone</span></div>
                    <div>
                        <div class="font-label-md text-label-md text-text-crisp font-semibold">Apple Pay</div>
                        <div class="font-body-sm text-body-sm text-text-muted">Encrypted Token Active</div>
                    </div>
                </div>
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-text-muted">LINKED</span>
            </div>
            <button class="mt-space-xs w-full py-space-xs rounded-lg bg-surface-slate hover:bg-surface-elevated text-text-crisp hover:text-accent-hyper-emerald font-label-sm text-label-sm uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">add_circle</span><span>+ Add New Payment Token</span>
            </button>
        </div>
    </x-profile-card>

    {{-- Ecological impact --}}
    <x-profile-card class="relative overflow-hidden">
        <x-card-header icon="forest" icon-class="text-accent-electric-lime" title="Ecological Impact">
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-accent-electric-lime font-semibold">{{ $impact['percent'] }}% GOAL</span>
        </x-card-header>
        <div class="mt-space-md flex flex-col gap-space-sm">
            <div class="flex items-center justify-between font-body-sm text-body-sm">
                <span class="text-on-surface-variant font-medium">Sonoma Micro-Basin Reforestation</span>
                <span class="text-text-crisp font-semibold">{{ $impact['done'] }} / {{ $impact['goal'] }} Harvests</span>
            </div>
            <div class="w-full bg-surface-slate h-2.5 rounded-full overflow-hidden p-0.5 shadow-inner">
                <div class="bg-gradient-to-r from-accent-hyper-emerald to-accent-electric-lime h-full rounded-full transition-all duration-500 shadow-[0_0_12px_rgba(0,245,155,0.4)]" style="width: {{ $impact['percent'] }}%"></div>
            </div>
            <div class="font-body-sm text-body-sm text-text-muted leading-relaxed">{{ $impact['goal'] - $impact['done'] }} more verified biodynamic dispatches until next native redwood tree is planted and geotagged to your Bio-Key.</div>
            <div class="mt-space-xs bg-surface-slate/70 p-space-sm rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-accent-electric-lime text-[18px]">psychiatry</span><div class="font-label-md text-label-md text-text-crisp">Regenerative Soil Micro-Credit</div></div>
                <span class="font-label-sm text-label-sm text-accent-electric-lime font-semibold">+$0.75 / order</span>
            </div>
        </div>
    </x-profile-card>

    {{-- Security --}}
    <x-profile-card>
        <x-card-header icon="encrypted" title="Security & Cryptography">
            <span class="material-symbols-outlined text-accent-hyper-emerald text-[18px]">verified_user</span>
        </x-card-header>
        <div class="mt-space-md flex flex-col gap-space-sm">
            <div class="flex items-start gap-space-sm bg-surface-slate/60 p-space-sm rounded-lg">
                <span class="material-symbols-outlined text-accent-hyper-emerald text-[20px] mt-0.5">key</span>
                <div><div class="font-label-md text-label-md text-text-crisp font-semibold">WebAuthn Hardware Passkey</div><div class="font-body-sm text-body-sm text-text-muted">Instant biometric scan bound to YubiKey 5C</div></div>
            </div>
            <div class="flex items-start gap-space-sm bg-surface-slate/60 p-space-sm rounded-lg">
                <span class="material-symbols-outlined text-accent-hyper-emerald text-[20px] mt-0.5">shield</span>
                <div><div class="font-label-md text-label-md text-text-crisp font-semibold">256-Bit Cold-Vault Encrypted</div><div class="font-body-sm text-body-sm text-text-muted">End-to-end biological nutritional records</div></div>
            </div>
            <button class="mt-space-xs w-full py-space-xs rounded-lg bg-surface-slate hover:bg-surface-elevated text-text-crisp hover:text-accent-hyper-emerald font-label-sm text-label-sm uppercase tracking-wider font-semibold transition-colors flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">passkey</span><span>Manage Passkeys &amp; Sessions</span>
            </button>
        </div>
    </x-profile-card>
</div>
