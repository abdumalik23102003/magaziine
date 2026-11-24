@if (session('status'))
    <div class="mb-6 rounded-xl border border-primary/30 bg-primary/10 px-4 py-3 text-sm font-mono text-primary" role="status">
        {{ session('status') }}
    </div>
@endif

@foreach (['cart', 'quantity', 'variant_id'] as $flashKey)
    @error($flashKey)
        <div class="mb-6 rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm font-mono text-red-300" role="alert">
            {{ $message }}
        </div>
    @enderror
@endforeach
