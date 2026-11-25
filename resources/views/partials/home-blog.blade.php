{{-- $posts: PostService::latest() (HomeController). Maqola bo'lmasa blok chiqmaydi. --}}
@if (count($posts) > 0)
<section class="mt-14">
    <x-section-heading eyebrow="From the journal" title="Fresh Reads" sub="Recipes, farm stories and seasonal tips" :href="route('blog.index')" cta="Read All Posts" />
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($posts as $post)
            <x-blog-card :post="$post" />
        @endforeach
    </div>
</section>
@endif
