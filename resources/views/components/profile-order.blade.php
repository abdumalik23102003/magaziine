@props(['order'])

<div class="py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('orders.show', $order) }}" class="font-display font-bold text-text-crisp hover:text-primary transition-colors">Order {{ $order->number }}</a>
            <span class="px-2 py-0.5 rounded-full border text-[10px] font-mono font-bold uppercase tracking-wider {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
        </div>
        <p class="mt-1 text-xs font-mono text-text-muted">
            {{ $order->created_at->format('d M Y, H:i') }} &middot; {{ $order->items_count }} {{ $order->items_count == 1 ? 'item' : 'items' }} &middot; <span class="text-text-crisp font-bold">${{ $order->total_price }}</span>
        </p>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('orders.show', $order) }}" class="rounded-full border border-border-glass px-4 py-2 text-[11px] font-mono font-bold uppercase tracking-wider text-text-muted hover:text-text-crisp hover:border-primary/40 transition-colors">Details</a>
        <form method="POST" action="{{ route('orders.reorder', $order) }}">
            @csrf
            <button type="submit" class="rounded-full bg-primary/10 border border-primary/30 px-4 py-2 text-[11px] font-mono font-bold uppercase tracking-wider text-primary hover:bg-primary hover:text-black transition-colors">Reorder</button>
        </form>
    </div>
</div>
