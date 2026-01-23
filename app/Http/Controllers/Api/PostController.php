<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;

class PostController extends Controller
{
    //
    public function index()
    {
        $posts = Post::all();
        return $posts;
    }

    public function show($post)
    {
        // $post = Post::find($id);
        return $post;
    }

}
