<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Services\CommentService;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function __construct(private CommentService $comments) {}

    public function storeProduct(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->isVisible(), 404);

        return $this->store($request, $product, route('products.show', $product->slug));
    }

    public function storePost(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->isLive(), 404);

        return $this->store($request, $post, route('blog.show', $post));
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        abort_unless($this->comments->canDelete($request->user(), $comment), 403);

        $this->comments->delete($comment);

        return back()->with('status', 'Comment deleted.');
    }

    private function store(Request $request, Model $item, string $url): RedirectResponse
    {
        try {
            $data = $request->validate([
                'body' => ['nullable', 'string', 'max:'.CommentService::MAX_LENGTH],
                'rating' => ['nullable', 'integer', 'between:1,5'],
                'parent_id' => ['nullable', 'integer'],
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo($url.'#discussion');
        }

        try {
            $comment = $this->comments->add(
                $request->user(),
                $item,
                (string) ($data['body'] ?? ''),
                isset($data['rating']) ? (int) $data['rating'] : null,
                isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            );
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['body' => $e->getMessage()])->redirectTo($url.'#discussion');
        }

        return redirect()->to($url.'#comment-'.$comment->id)->with('status', 'Thank you, your comment was posted.');
    }
}
