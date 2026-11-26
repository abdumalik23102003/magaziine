<x-filament-panels::page>
    <div wire:poll.5s style="display:grid;grid-template-columns:minmax(260px,340px) 1fr;gap:1rem;align-items:start">
        <div style="display:flex;flex-direction:column;gap:.5rem">
            <div style="display:flex;flex-wrap:wrap;gap:.25rem">
                @foreach (['mine' => 'Mine', 'unassigned' => 'Unassigned', 'open' => 'Open', 'closed' => 'Closed'] as $key => $label)
                    <x-filament::button size="xs" :color="$filter === $key ? 'primary' : 'gray'" wire:click="setFilter('{{ $key }}')">{{ $label }}</x-filament::button>
                @endforeach
            </div>

            @forelse ($this->conversations as $c)
                <button type="button" wire:click="select({{ $c->id }})" wire:key="conv-{{ $c->id }}"
                    class="fi-section" style="text-align:left;padding:.75rem;border-radius:.75rem;{{ $selectedId === $c->id ? 'outline:2px solid var(--primary-500, #d946ef);' : '' }}">
                    <div style="display:flex;justify-content:space-between;gap:.5rem">
                        <strong>{{ $c->user?->name ?? ($c->guest_name ?: 'Mehmon') }}</strong>
                        @if ($c->unread_count > 0)
                            <x-filament::badge color="danger">{{ $c->unread_count }}</x-filament::badge>
                        @endif
                    </div>
                    <div style="font-size:.75rem;opacity:.7">
                        {{ $c->status->value }} · {{ $c->operator?->name ?? 'biriktirilmagan' }} · {{ $c->last_message_at?->diffForHumans() }}
                    </div>
                </button>
            @empty
                <p style="opacity:.7">Suhbatlar yo'q.</p>
            @endforelse
        </div>

        <div class="fi-section" style="padding:1rem;border-radius:.75rem">
            @if ($this->selected)
                @php($sel = $this->selected)
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                    <strong>{{ $sel->user?->name ?? ($sel->guest_name ?: 'Mehmon') }}
                        <span style="font-weight:400;opacity:.7">({{ $sel->status->value }}, {{ $sel->operator?->name ?? 'biriktirilmagan' }})</span>
                    </strong>
                    <div style="display:flex;flex-wrap:wrap;gap:.25rem">
                        @if ($sel->status->value !== 'closed')
                            <x-filament::button size="xs" wire:click="assignToMe">Assign to me</x-filament::button>
                            <x-filament::button size="xs" color="danger" wire:click="closeConversation" wire:confirm="Suhbatni yopasizmi?">Close</x-filament::button>
                        @else
                            <x-filament::button size="xs" color="success" wire:click="reopenConversation">Reopen</x-filament::button>
                        @endif
                    </div>
                </div>

                @if (\App\Support\Staff::isAdmin(auth()->user()))
                    <div style="display:flex;gap:.5rem;margin-bottom:.75rem">
                        <select wire:model="transferTo" class="fi-input" style="flex:1;border-radius:.5rem;padding:.35rem">
                            <option value="">Operatorga o'tkazish...</option>
                            @foreach ($this->operators as $op)
                                <option value="{{ $op->id }}">{{ $op->name }}</option>
                            @endforeach
                        </select>
                        <x-filament::button size="xs" color="gray" wire:click="transferConversation">O'tkazish</x-filament::button>
                    </div>
                @endif

                <div style="max-height:26rem;overflow-y:auto;display:flex;flex-direction:column;gap:.4rem;margin-bottom:.75rem">
                    @foreach ($this->thread as $m)
                        <div wire:key="msg-{{ $m->id }}" style="max-width:80%;{{ $m->sender_type->value === 'operator' ? 'align-self:flex-end;' : ($m->sender_type->value === 'system' ? 'align-self:center;opacity:.7;font-size:.8rem;' : '') }}">
                            <div style="white-space:pre-wrap;word-break:break-word;padding:.5rem .75rem;border-radius:.75rem;background:{{ $m->sender_type->value === 'operator' ? 'rgba(217,70,239,.2)' : 'rgba(127,127,127,.15)' }}">{{ $m->body }}</div>
                            <div style="font-size:.7rem;opacity:.6">{{ $m->created_at?->format('d.m H:i') }}</div>
                        </div>
                    @endforeach
                </div>

                @if ($sel->status->value !== 'closed')
                    <form wire:submit="sendReply" style="display:flex;flex-direction:column;gap:.5rem">
                        <select x-on:change="if ($event.target.value) { $wire.insertCanned($event.target.value); $event.target.value = '' }" class="fi-input" style="border-radius:.5rem;padding:.35rem">
                            <option value="">Tayyor javob qo'shish...</option>
                            @foreach ($this->canned as $canned)
                                <option value="{{ $canned->id }}">{{ $canned->title }}</option>
                            @endforeach
                        </select>
                        <textarea wire:model="reply" rows="3" maxlength="{{ (int) config('support.max_length') }}" placeholder="Javob yozing..."
                            class="fi-input" style="width:100%;border-radius:.5rem;padding:.5rem"></textarea>
                        <div><x-filament::button type="submit" size="sm">Reply</x-filament::button></div>
                    </form>
                @endif
            @else
                <p style="opacity:.7">Chapdan suhbatni tanlang.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
