@php
    $steps = [
        ['n' => 1, 'title' => 'Delivery & Speed',        'sub' => 'Configuring Telemetry',     'state' => 'active'],
        ['n' => 2, 'title' => 'Payment Clearance',       'sub' => 'Encrypted Tokenization',    'state' => 'next'],
        ['n' => 3, 'title' => 'Final Review & Dispatch', 'sub' => 'Thermal Seal & Tracking',   'state' => 'later'],
    ];
@endphp
<div class="w-full bg-surface-carbon/90 p-space-md rounded-xl shadow-xl">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md relative">
        @foreach ($steps as $s)
            @php $active = $s['state'] === 'active'; @endphp
            <div class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg {{ $active ? 'bg-surface-slate/90 shadow-[0_0_25px_-5px_rgba(0,245,155,0.25)]' : 'bg-surface-obsidian/60' }} {{ $s['state'] === 'next' ? 'opacity-70' : '' }} {{ $s['state'] === 'later' ? 'opacity-50' : '' }}">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-headline-sm text-headline-sm {{ $active ? 'bg-primary-container text-surface-obsidian font-bold shadow-[0_0_12px_rgba(0,245,155,0.6)]' : 'bg-surface-elevated text-text-muted font-semibold' }}">{{ $s['n'] }}</div>
                <div class="flex flex-col">
                    <span class="font-label-lg text-label-lg uppercase tracking-wider {{ $active ? 'text-text-crisp' : ($s['state'] === 'next' ? 'text-on-surface-variant' : 'text-text-muted') }}">{{ $s['title'] }}</span>
                    @if ($active)
                        <span class="font-body-sm text-body-sm text-primary-container flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span> {{ $s['sub'] }}</span>
                    @else
                        <span class="font-body-sm text-body-sm text-text-muted">{{ $s['sub'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
