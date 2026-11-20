<section class="py-space-xl">
    <div class="flex flex-col gap-space-lg">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-md pb-space-sm">
            <div>
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-hyper-emerald">Cellular Transparency Ledger</span>
                <h2 class="font-headline-md text-headline-md text-text-crisp font-semibold tracking-tight">Biological Audit &amp; Traceability</h2>
            </div>
            <div class="flex items-center gap-1 bg-surface-carbon p-1 rounded-lg" id="audit-tabs">
                @foreach ([['lab', 'Bio-Telemetry & Lab'], ['timeline', 'Cold-Chain Timeline'], ['culinary', 'Storage & Pairing']] as $i => [$key, $label])
                    <button type="button" data-tab="{{ $key }}" class="telemetry-tab px-space-md py-space-xs rounded font-label-md text-label-md uppercase tracking-wider transition-all {{ $i === 0 ? 'bg-surface-elevated text-accent-hyper-emerald' : 'text-text-muted hover:text-text-crisp' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Tab 1: lab metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-gutter" id="content-lab">
            <x-lab-metric label="Sugar Balance Index" icon="water_drop" value="14.2°" caption="Peak Brix Balance" text="Naturally high sweetness-to-acidity lipid index, indicating prolonged tree maturation." :bar="88"/>
            <x-lab-metric label="Oleic Monounsaturated" icon="science" icon-class="text-accent-electric-lime" value="71.4%" value-class="text-accent-electric-lime" caption="Lipid Purity Quotient" caption-class="text-text-crisp" text="Ultra-dense concentration of heart-healthy C18H34O2 fatty acids for supreme velvety mouthfeel." :bar="92" bar-class="bg-accent-electric-lime"/>
            <x-lab-metric label="Hydration Envelope" icon="opacity" value="68.2%" caption="Hydration Saturation" text="Moisture is suspended uniformly in the plant matrix without excess watery dilution." :bar="68"/>
            <x-lab-metric label="Spectrometry Verification" icon="verified_user" value="0.00" value-class="text-accent-hyper-emerald" caption="PPM Synthetic Residues" caption-class="text-text-crisp" text="Complete mass-spec screening: 0 synthetic nitrates, zero glyphosates, zero chemical wax coatings." footer="Passed ISO-17025 Protocol"/>
        </div>

        {{-- Tab 2: timeline --}}
        <div class="hidden bg-surface-carbon p-space-lg rounded-xl shadow-md" id="content-timeline">
            <div class="relative pl-6 md:pl-8 space-y-space-lg">
                @foreach ($timeline as $t)
                    <x-timeline-item :time="$t['time']" :title="$t['title']" :text="$t['text']" :current="$t['current'] ?? false" :glow="$loop->first"/>
                @endforeach
            </div>
        </div>

        {{-- Tab 3: storage & pairing --}}
        <div class="hidden grid grid-cols-1 md:grid-cols-3 gap-gutter" id="content-culinary">
            <x-info-card icon="ac_unit" title="Storage Temperature" text="Store unripened fruit at 10-12°C for controlled enzyme maturation. Move to 4°C vegetable crisper drawer once peak firmness yield is reached."/>
            <x-info-card icon="restaurant" icon-class="text-accent-electric-lime" title="Sommelier Pairings" text="Flaky cold-evaporated Maldon sea salt crystals, wood-fired heritage sourdough, single-estate cold-pressed Persian lime olive oil."/>
            <x-info-card icon="nutrition" title="Nutritional Synergy" text="Lipid profile enhances fat-soluble vitamin absorption (A, D, E, K) by 420% when consumed alongside leafy botanicals and crucifers."/>
        </div>
    </div>
</section>
