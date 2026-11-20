<x-profile-card class="p-space-md md:p-space-lg">
    <div class="flex items-center justify-between pb-space-md border-b border-surface-container-high/40">
        <div>
            <span class="font-label-sm text-label-sm uppercase tracking-widest text-text-muted">Verified Traceability Ledger</span>
            <h2 class="font-title text-title text-text-crisp font-semibold tracking-tight mt-0.5">Recent Biological Consignments</h2>
        </div>
        <button class="font-label-md text-label-md uppercase tracking-wider text-accent-hyper-emerald hover:underline flex items-center gap-1">
            <span>Full Ledger ({{ $user['orders'] }})</span><span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </button>
    </div>
    <div class="divide-y divide-surface-container-high/40">
        @foreach ($orders as $o)
            <x-profile-order :number="$o['number']" :status="$o['status']" :recent="$loop->first" :total="$o['total']"
                :items="$o['items']" :summary="$o['summary']" :variance="$o['variance']" :origin="$o['origin']"/>
        @endforeach
    </div>
</x-profile-card>
