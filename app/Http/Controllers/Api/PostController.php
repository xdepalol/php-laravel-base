<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Support\ContentSanitizer;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PhpParser\Node\Expr\FuncCall;

class PostController extends Controller
{
    const USER_PROJECTION = "id,name,surname1";
    const CATEGORY_PROJECTION = "id,name";

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('post-list');

        $posts = Post::with(['categories', 'user:' . self::USER_PROJECTION])->get();
        return $posts;
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $this->authorize('post-view');

        $post->load('user:' . self::USER_PROJECTION, 'categories:' . self::CATEGORY_PROJECTION);
        return $post;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $this->authorize('post-delete');

        if (!$post->isAuthorized()) {
            return response()->json([
                "message" => "No permission to delete this post"
            ], 403);
        }

        $post->delete();
        return $post;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $this->authorize('post-create');

        //$data = $request->all();
        $data = $request->validated();
        $data['content'] = ContentSanitizer::sanitize($data['content']);
        $data['user_id'] = auth()->id();
        $post = Post::create($data);
        $post->categories()->attach($request->categories);
        return $post;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('post-edit');

        $data = $request->validated();
        // categories
        $categoryIds = $data['categories'] ?? [];
        unset($data['categories']);

        // actualitza dades
        $post->update($data);
        $post->categories()->sync($categoryIds);

        // materialitza navegacions
        $post->load('user:' . self::USER_PROJECTION, 'categories:' . self::CATEGORY_PROJECTION);
        return $post;
    }
}
