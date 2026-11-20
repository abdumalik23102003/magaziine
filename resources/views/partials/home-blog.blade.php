{{-- $posts comes from pages/home.blade.php --}}
<section class="mt-14">
    <x-section-heading eyebrow="From the journal" title="Fresh Reads" sub="Recipes, farm stories and seasonal tips" href="#" cta="Read All Posts" />
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($posts as $post)
            <x-blog-card :img="$post['img']" :tag="$post['tag']" :date="$post['date']" :title="$post['title']" :excerpt="$post['excerpt']" />
        @endforeach
    </div>
</section>
