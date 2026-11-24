@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null, 'required' => false, 'placeholder' => null, 'class' => ''])

<div class="flex flex-col gap-1.5 {{ $class }}">
    <label for="{{ $name }}" class="text-[10px] font-mono font-bold uppercase tracking-widest text-primary">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @required($required)
        class="w-full bg-surface-carbon border border-border-glass hover:border-primary/40 focus:border-primary rounded-xl py-2.5 px-4 text-sm text-text-crisp placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all @error($name) border-red-400/60 @enderror"
    >
    @error($name)
        <p class="text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>
