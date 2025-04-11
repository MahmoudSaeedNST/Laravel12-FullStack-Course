<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function addcomment(Request $request, string $id){
        $data = $request->validate([
            'content' => 'required|string',
        ]);
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }
        $post->comments()->create([
            'content' => $data['content']
        ]);
        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $post->comments,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::with('comments')->get();
        return response()->json($posts);
        /* return PostResource::collection($posts)->additional([
            'message' => 'Get all posts',
            'status' => 200
        ]); */
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


    public function show()
    {

        $post = (object)[
            'id' => 1,
            'title' => 'Welcome to API Resources',
            'content' => 'This is a static post used to demonstrate Laravel resources.'
        ];

        return new PostResource($post);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(PostRequest $request, string $id)
    {
        // validation
        $data = $request->validated();
        $selectedpost = array_filter($this->posts, function ($filterdpost) use ($id) {
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
        $this->posts = array_filter($this->posts, function ($filterdpost) use ($id) {
            return $filterdpost['id'] != $id; // ignore the post with the id
        });
        return response()->json([
            'message' => 'Post deleted successfully',
            'data' => $this->posts
        ]);
    }
}
