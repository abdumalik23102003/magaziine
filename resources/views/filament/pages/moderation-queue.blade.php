<x-filament-panels::page>
    <div wire:poll.15s style="display:grid;grid-template-columns:minmax(260px,360px) 1fr;gap:1rem;align-items:start">
        <div style="display:flex;flex-direction:column;gap:.5rem">
            @forelse ($this->items as $item)
                <button type="button" wire:click="select('{{ $item['key'] }}')" wire:key="q-{{ $item['key'] }}"
                    class="fi-section" style="text-align:left;padding:.75rem;border-radius:.75rem;{{ $selectedKey === $item['key'] ? 'outline:2px solid var(--primary-500, #d946ef);' : '' }}">
                    <div style="display:flex;justify-content:space-between;gap:.5rem">
                        <strong>{{ $item['title'] }}</strong>
                        <x-filament::badge :color="$item['type'] === 'product' ? 'info' : 'warning'">{{ $item['type'] === 'product' ? 'Mahsulot' : 'Post' }}</x-filament::badge>
                    </div>
                    <div style="font-size:.75rem;opacity:.7">{{ $item['brand'] ?? 'brendsiz' }} · {{ $item['updated_at']?->diffForHumans() }}</div>
                </button>
            @empty
                <p style="opacity:.7">Navbat bo'sh.</p>
            @endforelse
        </div>

        <div class="fi-section" style="padding:1rem;border-radius:.75rem">
            @if ($this->selected)
                @php($sel = $this->selected)
                <h3 style="font-weight:600;font-size:1.1rem">{{ $sel['title'] }}</h3>
                <p style="font-size:.8rem;opacity:.7;margin-bottom:.75rem">{{ $sel['type'] === 'product' ? 'Mahsulot' : 'Post' }} · {{ $sel['brand'] ?? 'brendsiz' }}</p>
                <div style="white-space:pre-wrap;word-break:break-word;max-height:22rem;overflow-y:auto;padding:.75rem;border-radius:.5rem;background:rgba(127,127,127,.12);margin-bottom:.75rem">{{ $sel['preview'] }}</div>

                <textarea wire:model="reason" rows="2" maxlength="500" placeholder="Rad etish sababi (Reject uchun majburiy)"
                    class="fi-input" style="width:100%;border-radius:.5rem;padding:.5rem;margin-bottom:.5rem"></textarea>
                <div style="display:flex;gap:.5rem">
                    <x-filament::button color="success" wire:click="approve" wire:confirm="Tasdiqlaysizmi?">Approve</x-filament::button>
                    <x-filament::button color="danger" wire:click="reject">Reject</x-filament::button>
                </div>
            @else
                <p style="opacity:.7">Chapdan elementni tanlang.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
