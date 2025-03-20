<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public $posts = [
        [
            'id' => 1,
            'title' => 'Post 1',
            'content' => 'Content of post 1'
        ],
        [
            'id' => 2,
            'title' => 'Post 2',
            'content' => 'Content of post 2'
        ],
        [
            'id' => 3,
            'title' => 'Post 3',
            'content' => 'Content of post 3'
        ],
        [
            'id' => 4,
            'title' => 'Post 4',
            'content' => 'Content of post 4'
        ],
    ];
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return response()->json([
            'message' => 'List of posts',
            'data' => $this->posts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostRequest $request)
    {

        // validation
        $data = $request->validated();
        // $request->input()
        $newPost = [
            'id' => count($this->posts) + 1,
            ...$data
        ];
        array_push($this->posts, $newPost);
        return response()->json([
            'message' => 'Post created successfully',
            'data' => $this->posts
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // []
        $post = array_filter($this->posts, function ($filterdpost) use ($id){
            return $filterdpost['id'] == $id;
        });
        return response()->json([
            'message' => 'Post found',
            'data' => array_values($post)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostRequest $request, string $id)
    {
        // validation
        $data = $request->validated();
        $selectedpost = array_filter($this->posts, function ($filterdpost) use ($id){
            return $filterdpost['id'] == $id;
        });
        $post = array_values($selectedpost)[0];
        $post['title'] = $data['title'];
        $post['content'] = $data['content'];
        return response()->json([
            'message' => 'Post updated successfully',
            'data' => $post
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->posts = array_filter($this->posts, function ($filterdpost) use ($id){
            return $filterdpost['id'] != $id; // ignore the post with the id
        });
        return response()->json([
            'message' => 'Post deleted successfully',
            'data' => $this->posts
        ]);
    }
}
