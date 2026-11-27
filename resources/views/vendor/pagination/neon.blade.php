@if ($paginator->hasPages())
    @php
        $base = 'inline-flex h-10 min-w-10 items-center justify-center rounded-full border px-3 text-sm font-mono font-bold transition-all';
        $idle = 'border-white/10 bg-white/5 text-text-crisp hover:border-primary/60 hover:text-primary';
        $off = 'border-white/5 bg-white/[0.02] text-zinc-600 cursor-not-allowed';
        $on = 'border-primary bg-primary text-black shadow-[0_0_16px_var(--primary-glow)]';
    @endphp

    <nav role="navigation" aria-label="Pagination" class="mt-12 flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
        <p class="text-xs font-mono text-text-muted">
            Showing
            <span class="font-bold text-text-crisp">{{ $paginator->firstItem() }}</span>-<span class="font-bold text-text-crisp">{{ $paginator->lastItem() }}</span>
            of
            <span class="font-bold text-text-crisp">{{ $paginator->total() }}</span>
            results
        </p>

        <ul class="flex flex-wrap items-center justify-center gap-1.5">
            {{-- Oldingi --}}
            @if ($paginator->onFirstPage())
                <li aria-disabled="true">
                    <span class="{{ $base }} {{ $off }}" aria-label="Previous page">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/></svg>
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} {{ $idle }}" aria-label="Previous page">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/></svg>
                    </a>
                </li>
            @endif

            {{-- Telefonda faqat joriy sahifa --}}
            <li class="sm:hidden px-3 text-xs font-mono text-text-muted">
                Page <span class="font-bold text-text-crisp">{{ $paginator->currentPage() }}</span> / {{ $paginator->lastPage() }}
            </li>

            {{-- Sahifa raqamlari --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="hidden sm:block" aria-disabled="true"><span class="{{ $base }} border-transparent text-zinc-500">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="hidden sm:block" aria-current="page"><span class="{{ $base }} {{ $on }}">{{ $page }}</span></li>
                        @else
                            <li class="hidden sm:block"><a href="{{ $url }}" class="{{ $base }} {{ $idle }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Keyingi --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} {{ $idle }}" aria-label="Next page">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                    </a>
                </li>
            @else
                <li aria-disabled="true">
                    <span class="{{ $base }} {{ $off }}" aria-label="Next page">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
