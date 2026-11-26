@php
    $trigger = 'flex cursor-pointer list-none items-center gap-2 rounded-full border border-border-glass bg-surface-carbon/80 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-text-crisp transition-colors hover:border-primary/40 hover:text-primary [&::-webkit-details-marker]:hidden';
    $panel = 'mt-2 rounded-2xl border border-border-glass bg-surface-carbon p-3 md:absolute md:left-0 md:top-full md:z-50 md:mt-2 md:shadow-2xl';
    $link = 'flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-text-muted transition-colors hover:bg-white/5 hover:text-primary';
    $heading = 'mb-2 px-3 text-[10px] font-mono font-bold uppercase tracking-widest text-primary';
@endphp

<div class="relative z-40 border-t border-border-glass/60 bg-surface-obsidian/60 px-4 py-2 sm:px-6 lg:px-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-2 md:flex-row md:items-start md:gap-3">

        <details data-dropdown class="group relative">
            <summary class="{{ $trigger }}">
                <i class="bi bi-compass text-primary"></i> Sahifalar
                <i class="bi bi-chevron-down text-[10px] transition-transform group-open:rotate-180"></i>
            </summary>
            <div data-dropdown-panel class="{{ $panel }} md:w-64">
                <ul class="space-y-0.5">
                    @foreach ($navPages as $item)
                        <li>
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" class="{{ $link }}"><i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}</a>
                            @else
                                <button type="button" data-dispatch="{{ $item['event'] }}" class="{{ $link }} w-full text-left"><i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}</button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </details>

        <details data-dropdown class="group relative">
            <summary class="{{ $trigger }}">
                <i class="bi bi-grid-fill text-primary"></i> Katalog
                <i class="bi bi-chevron-down text-[10px] transition-transform group-open:rotate-180"></i>
            </summary>
            <div data-dropdown-panel class="{{ $panel }} md:w-[44rem]">
                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <p class="{{ $heading }}">Kategoriyalar</p>
                        <ul class="space-y-0.5">
                            @forelse ($navCatalog['categories'] as $category)
                                <li>
                                    <a href="{{ route('catalog.category', $category['slug']) }}" class="{{ $link }} font-semibold text-text-crisp">{{ $category['name'] }}</a>
                                    @if ($category['children'])
                                        <ul class="ml-4 border-l border-border-glass pl-2">
                                            @foreach ($category['children'] as $child)
                                                <li><a href="{{ route('catalog.category', $child['slug']) }}" class="{{ $link }} py-1 text-xs">{{ $child['name'] }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @empty
                                <li class="px-3 text-xs text-text-muted">Hozircha kategoriya yo'q</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <p class="{{ $heading }}">Tanlangan brendlar</p>
                        <ul class="space-y-0.5">
                            @foreach ($navCatalog['brands'] as $brand)
                                <li><a href="{{ route('brands.show', $brand['slug']) }}" class="{{ $link }}">{{ $brand['name'] }}</a></li>
                            @endforeach
                            <li><a href="{{ route('brands.index') }}" class="{{ $link }} font-bold text-primary">Barcha brendlar <i class="bi bi-arrow-right"></i></a></li>
                        </ul>
                        <a href="{{ route('catalog.index', ['discounted' => 1]) }}" class="mt-3 inline-flex items-center gap-2 rounded-full border border-accent-lime/30 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-accent-lime transition-colors hover:bg-accent-lime hover:text-black"><i class="bi bi-stars"></i> Chegirmalar</a>
                    </div>

                    <div>
                        <p class="{{ $heading }}">Yangi postlar</p>
                        <ul class="space-y-0.5">
                            @forelse ($navCatalog['posts'] as $post)
                                <li><a href="{{ route('blog.show', $post['slug']) }}" class="{{ $link }}">{{ $post['title'] }}</a></li>
                            @empty
                                <li class="px-3 text-xs text-text-muted">Postlar yo'q</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </details>
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/dropdowns.js') }}"></script>
    @endpush
@endonce
