{{-- Baholash va izohlar bloki. $discussion: CommentService::discussion() natijasi. --}}
@php
    $item = $discussion['item'];
    $summary = $discussion['summary'];
    $roots = $discussion['roots'];
    $authUser = auth()->user();
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
    $mainFormActive = ! old('parent_id');
    $input = 'w-full rounded-2xl border border-border-glass bg-surface-carbon px-4 py-3 text-sm text-text-crisp placeholder:text-zinc-500 focus:border-primary focus:outline-none';
@endphp

<section id="discussion" class="mt-16 scroll-mt-24">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[11px] font-mono font-bold uppercase tracking-widest text-primary">Community</p>
            <h2 class="font-display text-2xl font-extrabold text-text-crisp sm:text-3xl">Ratings &amp; discussion</h2>
        </div>
        <p class="text-xs font-mono text-text-muted">
            {{ number_format((int) $item->comments_count) }} {{ \Illuminate\Support\Str::plural('comment', (int) $item->comments_count) }}
        </p>
    </div>

    @if ($summary['count'] > 0)
        <div class="dark-glass-card mb-8 grid items-center gap-6 rounded-3xl p-6 sm:grid-cols-[auto_1fr]">
            <div class="text-center sm:text-left">
                <p class="font-display text-5xl font-extrabold text-text-crisp">{{ number_format($summary['average'], 1) }}</p>
                <x-stars :value="$summary['average']" size="text-base" class="mt-1" />
                <p class="mt-1 text-xs font-mono text-text-muted">
                    {{ number_format($summary['count']) }} {{ \Illuminate\Support\Str::plural('rating', $summary['count']) }}
                </p>
            </div>
            <div class="space-y-1.5">
                @foreach ($summary['distribution'] as $star => $total)
                    <div class="flex items-center gap-3 text-xs font-mono text-text-muted">
                        <span class="w-8 shrink-0">{{ $star }} <i class="bi bi-star-fill text-amber-400"></i></span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/5">
                            <div class="h-full rounded-full bg-primary" style="width: {{ round($total / $summary['count'] * 100) }}%"></div>
                        </div>
                        <span class="w-8 text-right">{{ $total }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @auth
        <form method="POST" action="{{ $discussion['storeRoute'] }}" class="dark-glass-card mb-8 rounded-3xl p-5">
            @csrf

            @unless ($discussion['userRated'])
                <p class="mb-2 text-[11px] font-mono font-bold uppercase tracking-widest text-text-muted">Your rating (optional)</p>
                <div class="star-input mb-4">
                    @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" id="rate-{{ $i }}" value="{{ $i }}" @checked($mainFormActive && (int) old('rating') === $i)>
                        <label for="rate-{{ $i }}" title="{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}"><i class="bi bi-star-fill"></i></label>
                    @endfor
                </div>
            @endunless

            <label for="comment-body" class="sr-only">Your comment</label>
            <textarea id="comment-body" name="body" rows="3" maxlength="{{ \App\Services\CommentService::MAX_LENGTH }}" placeholder="Share your experience or ask a question..." class="{{ $input }}">{{ $mainFormActive ? old('body') : '' }}</textarea>

            @if ($mainFormActive && $errors->any())
                <p class="mt-2 text-sm font-mono text-red-300" role="alert">{{ $errors->first('body') ?: $errors->first('rating') }}</p>
            @endif

            <div class="mt-4 flex items-center justify-between gap-4">
                <span class="text-xs font-mono text-zinc-500">Signed in as {{ $authUser->name }}</span>
                <button type="submit" class="water-ripple-target rounded-xl bg-primary px-6 py-2.5 text-sm font-display font-bold text-black transition-colors hover:bg-emerald-400">Post</button>
            </div>
        </form>
    @else
        <div class="dark-glass-card mb-8 flex flex-wrap items-center justify-between gap-4 rounded-3xl p-5">
            <p class="text-sm text-text-muted">Sign in to rate, comment and reply.</p>
            <a href="{{ $loginUrl }}" class="rounded-xl bg-primary px-6 py-2.5 text-sm font-display font-bold text-black transition-colors hover:bg-emerald-400">Sign in</a>
        </div>
    @endauth

    <div>
        @forelse ($roots as $comment)
            @include('partials.comment-node', ['comment' => $comment, 'children' => $discussion['children'], 'depth' => 0])
        @empty
            <p class="rounded-2xl border border-dashed border-border-glass px-4 py-8 text-center text-sm text-text-muted">No comments yet. Be the first to start the conversation.</p>
        @endforelse
    </div>

    {{ $roots->links() }}
</section>
