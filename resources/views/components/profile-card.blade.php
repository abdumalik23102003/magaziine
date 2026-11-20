{{-- Standard dark card used all over the profile page. $header is an optional slot for the title row. --}}
<div {{ $attributes->merge(['class' => 'bg-surface-carbon rounded-xl p-space-md shadow-xl']) }}>
    {{ $slot }}
</div>
