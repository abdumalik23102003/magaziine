<section class="mt-14 relative overflow-hidden rounded-3xl border border-border-glass bg-gradient-to-br from-surface-carbon via-surface-slate to-surface-obsidian p-8 sm:p-12">
    <div class="absolute inset-0 grid-bg-overlay opacity-30 pointer-events-none"></div>
    <div class="absolute -left-24 -bottom-24 w-96 h-96 bg-primary/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
        <div>
            <span class="text-xs font-mono font-bold tracking-widest uppercase text-primary">FoodMart app</span>
            <h2 class="font-display font-extrabold text-3xl sm:text-4xl text-text-crisp leading-tight mt-2 mb-4">
                Harvest in your pocket, <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent-lime">delivered in minutes.</span>
            </h2>
            <p class="text-sm sm:text-base text-text-muted max-w-md mb-8 leading-relaxed">
                Track every dispatch live, reorder your weekly basket in one tap and unlock app-only deals.
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="#" class="water-ripple-target block rounded-xl overflow-hidden border border-border-glass hover:border-primary/50 transition-all">
                    <img src="{{ asset('foodmart/images/app-store.jpg') }}" alt="Download on the App Store" class="h-12 w-auto" loading="lazy">
                </a>
                <a href="#" class="water-ripple-target block rounded-xl overflow-hidden border border-border-glass hover:border-primary/50 transition-all">
                    <img src="{{ asset('foodmart/images/google-play.jpg') }}" alt="Get it on Google Play" class="h-12 w-auto" loading="lazy">
                </a>
            </div>
        </div>
        <div class="flex justify-center md:justify-end">
            <img src="{{ asset('foodmart/images/phone.png') }}" alt="FoodMart mobile app" loading="lazy"
                 class="w-52 sm:w-60 drop-shadow-[0_24px_40px_rgba(0,245,155,0.2)] hover:-translate-y-2 transition-transform duration-500">
        </div>
    </div>
</section>
