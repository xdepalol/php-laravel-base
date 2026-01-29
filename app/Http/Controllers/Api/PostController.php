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
        $posts = Post::all();
        return $posts;
    }

    public function show(Post $post)
    {
        $post->load('user:' . self::USER_PROJECTION, 'categories:' . self::CATEGORY_PROJECTION);
        return $post;
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return $post;
    }

    public function store(StorePostRequest $request)
    {
        // $this->authorize('post-edit');

        //$data = $request->all();
        $data = $request->validated();
        $data['user_id'] = auth()->id();;
        $post = Post::create($data);
        $post->categories()->attach($request->categories);
        return $post;
    }

}
