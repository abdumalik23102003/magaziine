<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    public function __construct(private PostService $posts) {}

    public function index(): View
    {
        return view('pages.blog.index', [
            'posts' => $this->posts->listing(),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Blog', 'url' => null],
            ],
        ]);
    }

    public function show(Post $post): View
    {
        $post->loadMissing('brand');

        abort_unless($post->isLive(), 404);

        $crumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Blog', 'url' => route('blog.index')],
            ['label' => $post->title, 'url' => null],
        ];

        return view('pages.blog.show', [
            'post' => $post,
            'related' => $this->posts->related($post),
            'schema' => $this->posts->schema($post),
            'breadcrumbs' => $crumbs,
        ]);
    }
}
