<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\CommentService;
use App\Services\PostService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(private PostService $posts) {}

    public function index(Request $request): View
    {
        $search = $this->text($request->query('q'));
        $brand = $this->text($request->query('brand'));

        return view('pages.blog.index', [
            'posts' => $this->posts->listing($search, $brand),
            'brands' => $this->posts->brandFilters(),
            'filters' => ['q' => $search, 'brand' => $brand],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Blog', 'url' => null],
            ],
        ]);
    }

    public function show(Request $request, Post $post): View
    {
        $post->loadMissing('brand');

        // E'lon qilinmagan maqolani faqat Policy ruxsat bergan odam (muallif brend hamkori, admin, moderator) ko'radi.
        $preview = ! $post->isLive();
        abort_if($preview && ! $request->user()?->can('view', $post), 404);

        if (! $preview) {
            $this->posts->recordView($post, $request);
        }

        $crumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Blog', 'url' => route('blog.index')],
            ['label' => $post->title, 'url' => null],
        ];

        return view('pages.blog.show', [
            'post' => $post,
            'preview' => $preview,
            'related' => $this->posts->related($post),
            'schema' => $this->posts->schema($post),
            'breadcrumbs' => $crumbs,
            'discussion' => $preview ? null : app(CommentService::class)->discussion($post, $request),
        ]);
    }

    /** ?q=a&q[]=b kabi axlat qiymatlar sahifani buzmasligi uchun faqat qisqa matn qabul qilinadi. */
    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = mb_substr(trim($value), 0, 100);

        return $value === '' ? null : $value;
    }
}
