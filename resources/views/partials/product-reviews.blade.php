<section class="py-space-lg" id="reviews">
    <div class="bg-surface-carbon p-space-lg md:p-space-xl rounded-xl shadow-lg flex flex-col gap-space-lg">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter items-center pb-space-lg">
            <div class="md:col-span-4 flex items-center gap-space-lg">
                <div class="flex flex-col">
                    <span class="font-display text-display text-text-crisp font-bold leading-none">{{ $info['rating'] }}</span>
                    <x-star-rating :rating="$info['rating']" :size="20" class="mt-1"/>
                    <span class="font-body-sm text-body-sm text-text-muted mt-1">Based on {{ $info['reviews'] }} verified harvest deliveries</span>
                </div>
            </div>
            <div class="md:col-span-8 grid grid-cols-2 sm:grid-cols-3 gap-space-sm">
                <div class="bg-surface-slate p-space-sm rounded-lg"><div class="font-label-sm text-label-sm text-text-muted uppercase">Brix Accuracy Score</div><div class="font-headline-sm text-headline-sm text-accent-hyper-emerald font-bold mt-0.5">99.1%</div><span class="font-label-sm text-label-sm text-on-surface-variant">Lab matched</span></div>
                <div class="bg-surface-slate p-space-sm rounded-lg"><div class="font-label-sm text-label-sm text-text-muted uppercase">Cold Integrity Maintained</div><div class="font-headline-sm text-headline-sm text-accent-electric-lime font-bold mt-0.5">100%</div><span class="font-label-sm text-label-sm text-on-surface-variant">Zero thermal spikes</span></div>
                <div class="bg-surface-slate p-space-sm rounded-lg col-span-2 sm:col-span-1"><div class="font-label-sm text-label-sm text-text-muted uppercase">Creamy Texture Score</div><div class="font-headline-sm text-headline-sm text-text-crisp font-bold mt-0.5">4.9 / 5.0</div><span class="font-label-sm text-label-sm text-on-surface-variant">Blind sensory test</span></div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-space-sm">
            <div class="flex items-center gap-space-xs">
                @foreach ([['All Logs (124)', true], ['Verified Harvest (118)', false], ['Photos Included (42)', false]] as [$label, $on])
                    <button type="button" class="px-space-md py-space-xs rounded-full font-label-md text-label-md uppercase tracking-wider {{ $on ? 'bg-surface-elevated text-accent-hyper-emerald' : 'bg-surface-slate text-text-muted hover:text-text-crisp transition-colors' }}">{{ $label }}</button>
                @endforeach
            </div>
            <div class="font-label-sm text-label-sm text-text-muted uppercase">Sorted by Chronological Verification</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
            @foreach ($reviews as $r)
                <x-review-card :name="$r['name']" :badge="$r['badge']" :rating="$r['rating']" :text="$r['text']" :meta="$r['meta']"/>
            @endforeach
        </div>
    </div>
</section>
