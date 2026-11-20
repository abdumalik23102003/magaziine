@props(['number', 'status', 'recent' => false, 'total', 'items', 'summary', 'variance', 'origin'])

<div class="py-space-md flex flex-col md:flex-row md:items-center justify-between gap-space-md group">
    <div class="flex items-start gap-space-md">
        <div class="w-12 h-12 rounded-xl bg-surface-slate flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-accent-hyper-emerald text-[24px]">task_alt</span>
        </div>
        <div>
            <div class="flex items-center gap-space-xs flex-wrap">
                <span class="font-title text-title text-text-crisp font-semibold">Order #{{ $number }}</span>
                <span class="font-label-sm text-label-sm px-2 py-0.5 rounded font-semibold uppercase {{ $recent ? 'bg-accent-hyper-emerald/10 text-accent-hyper-emerald' : 'bg-surface-slate text-on-surface-variant' }}">{{ $status }}</span>
            </div>
            <div class="font-body-sm text-body-sm text-text-muted mt-1">${{ $total }} • {{ $items }} items • <span class="text-on-surface-variant">{{ $summary }}</span></div>
            <div class="font-label-sm text-label-sm text-outline mt-1">Cold-Chain Variance: <strong class="text-text-crisp font-normal">{{ $variance }}</strong> • Origin: {{ $origin }}</div>
        </div>
    </div>
    <div class="flex items-center gap-space-xs shrink-0 self-end md:self-center">
        <button class="font-label-md text-label-md uppercase tracking-wider px-3 py-1.5 rounded-lg bg-surface-slate hover:bg-surface-elevated text-on-surface-variant hover:text-text-crisp transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">receipt_long</span><span>View Digital Slip</span>
        </button>
        <button class="font-label-md text-label-md uppercase tracking-wider px-3 py-1.5 rounded-lg bg-surface-slate hover:bg-accent-hyper-emerald hover:text-surface-obsidian text-accent-hyper-emerald font-semibold transition-all flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">replay</span><span>Reorder All</span>
        </button>
    </div>
</div>
