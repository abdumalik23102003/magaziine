{{-- Bitta izoh va uning javoblari (rekursiv, chuqurlik cheklanmagan; ko'rinishdagi surilish 4 darajada to'xtaydi). --}}
@php
    $kids = $children[$comment->id] ?? [];
    $author = $comment->user?->name ?? 'Deleted user';
    $canDelete = app(\App\Services\CommentService::class)->canDelete(auth()->user(), $comment);
    $replyActive = (int) old('parent_id') === $comment->id;
    $wrap = match (true) {
        $depth === 0 => '',
        $depth <= 4 => 'ml-3 sm:ml-8 border-l border-border-glass pl-3 sm:pl-4',
        default => 'border-l border-border-glass pl-3',
    };
@endphp

<div id="comment-{{ $comment->id }}" class="scroll-mt-24 {{ $wrap }}">
    <article class="dark-glass-card mb-3 rounded-2xl p-4">
        <header class="flex flex-wrap items-center gap-2 text-xs">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/15 font-display font-bold text-primary" aria-hidden="true">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
            <span class="font-display font-bold text-text-crisp">{{ $author }}</span>
            @if ($comment->rating)
                <x-stars :value="$comment->rating" />
            @endif
            <time datetime="{{ $comment->created_at->toIso8601String() }}" class="font-mono text-zinc-500">{{ $comment->created_at->diffForHumans() }}</time>
        </header>

        @if ($comment->body !== '')
            <p class="mt-3 whitespace-pre-line break-words text-sm leading-relaxed text-text-muted">{{ $comment->body }}</p>
        @endif

        <div class="mt-3 flex flex-wrap items-start gap-4 text-xs font-mono font-bold uppercase tracking-wider">
            @auth
                <details class="min-w-[12rem] flex-1" @if ($replyActive) open @endif>
                    <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-primary hover:text-emerald-300">
                        <i class="bi bi-reply"></i> Reply @if (count($kids)) ({{ count($kids) }}) @endif
                    </summary>
                    <form method="POST" action="{{ $discussion['storeRoute'] ?? url()->current().'/comments' }}" class="mt-3 normal-case">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <label for="reply-{{ $comment->id }}" class="sr-only">Your reply</label>
                        <textarea id="reply-{{ $comment->id }}" name="body" rows="2" maxlength="{{ \App\Services\CommentService::MAX_LENGTH }}" placeholder="Write a reply..." class="w-full rounded-2xl border border-border-glass bg-surface-carbon px-4 py-3 text-sm text-text-crisp placeholder:text-zinc-500 focus:border-primary focus:outline-none">{{ $replyActive ? old('body') : '' }}</textarea>
                        @if ($replyActive && $errors->any())
                            <p class="mt-2 text-sm font-mono text-red-300" role="alert">{{ $errors->first('body') }}</p>
                        @endif
                        <button type="submit" class="mt-2 rounded-xl bg-primary px-5 py-2 text-xs font-display font-bold text-black transition-colors hover:bg-emerald-400">Send reply</button>
                    </form>
                </details>
            @endauth

            @if ($canDelete)
                <form method="POST" action="{{ route('comments.destroy', $comment) }}" onsubmit="return confirm('Delete this comment and all its replies?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 text-zinc-500 transition-colors hover:text-red-300"><i class="bi bi-trash"></i> Delete</button>
                </form>
            @endif
        </div>
    </article>

    @foreach ($kids as $child)
        @include('partials.comment-node', ['comment' => $child, 'children' => $children, 'depth' => $depth + 1])
    @endforeach
</div>
