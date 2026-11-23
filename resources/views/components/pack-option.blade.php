@props(['key', 'label', 'name', 'price', 'per', 'note', 'old' => null, 'badge' => null, 'noteClass' => 'text-accent-electric-lime', 'active' => false])

<button type="button"
    class="pack-btn p-space-sm rounded-lg text-left transition-all {{ $active ? 'active bg-surface-elevated ring-1 ring-accent-hyper-emerald shadow-sm' : 'bg-surface-slate hover:bg-surface-elevated' }}"
    data-pack="{{ $key }}" data-price="{{ $price }}" data-per="{{ $per }}" data-label="{{ $name }}" data-old="{{ $old }}" data-badge="{{ $badge }}">
    <div class="font-label-md text-label-md text-text-crisp font-semibold">{{ $label }}</div>
    <div class="font-body-sm text-body-sm font-bold mt-0.5 {{ $active ? 'text-accent-hyper-emerald' : 'text-text-crisp' }}">${{ number_format($price, 2) }}</div>
    <div class="font-label-sm text-label-sm mt-0.5 {{ $active ? 'text-text-muted' : $noteClass }}">{{ $note }}</div>
</button>
