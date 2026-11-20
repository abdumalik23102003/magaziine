<section class="py-space-xl">
    <div class="flex flex-col gap-space-lg">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-sm">
            <div>
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-accent-electric-lime">Nutritional Synergies</span>
                <h2 class="font-headline-md text-headline-md text-text-crisp font-semibold tracking-tight">Pairs Well With This Harvest</h2>
            </div>
            <a class="font-label-md text-label-md uppercase tracking-wider text-accent-hyper-emerald hover:underline flex items-center gap-1" href="#">Browse Orchard Complementary Catalog <span class="material-symbols-outlined text-[16px]">arrow_forward</span></a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
            @foreach ($related as $r)
                <x-recommend-card :name="$r['name']" :tag="$r['tag']" :tag-class="$r['tagClass']" :kicker="$r['kicker']" :meta="$r['meta']" :price="$r['price']" :img="$r['img']" :alt="$r['alt']" :add-name="$r['addName']"/>
            @endforeach
        </div>
    </div>
</section>
