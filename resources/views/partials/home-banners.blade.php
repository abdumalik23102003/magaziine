{{-- $banners comes from pages/home.blade.php --}}
<section class="mt-14 grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach ($banners as $b)
        <x-promo-banner :img="$b['img']" :kicker="$b['kicker']" :title="$b['title']" :glow="$b['glow']" />
    @endforeach
</section>
