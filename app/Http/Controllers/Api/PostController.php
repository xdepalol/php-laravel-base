<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use PhpParser\Node\Expr\FuncCall;

class PostController extends Controller
{
    const USER_PROJECTION = "id,name,surname1";
    const CATEGORY_PROJECTION = "id,name";

    //
    public function index()
    {
        $this->authorize('post-list');

        $posts = Post::with(['categories', 'user:' . self::USER_PROJECTION])->get();
        return $posts;
    }

    public function show(Post $post)
    {
        $this->authorize('post-list');

        $post->load('user:' . self::USER_PROJECTION, 'categories:' . self::CATEGORY_PROJECTION);
        return $post;
    }

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

    public function store(StorePostRequest $request)
    {
        $this->authorize('post-create');

        //$data = $request->all();
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $post = Post::create($data);
        $post->categories()->attach($request->categories);
        return $post;
    }

}
