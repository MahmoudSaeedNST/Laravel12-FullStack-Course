<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// route group 
/* Route::prefix('v1')->group(function () {
    // get to get all data
    // get posts
    Route::get('/posts', function () {
        // return posts data
        $data = [
            [
                'id' => 1,
                'title' => 'Post 1',
                'content' => 'Content of post 1'
            ],
            [
                'id' => 2,
                'title' => 'Post 2',
                'content' => 'Content of post 2'
            ]
        ];
        return response()->json([
            'message' => 'Get all posts',
            'data' => $data
        ]);
    });

    // post create new data
    Route::post('/posts', function (Request $request) {
        return response()->json([
            'message' => 'Create new post',
            'data' => $request->all()
        ]);
    });
    // put to update exist data
    Route::put('/posts/{id}', function (Request $request, $id) {
        return response()->json([
            'message' => 'Update post',
            'data' => $request->all()
        ]);
    });
    // delete to delete data
    Route::delete('/posts/{id}', function ($id) {
        return response()->json([
            'message' => 'Delete post: ' . $id,
        ]);
    });

    // get to get single data
    Route::get('/posts/{id}', function ($id) {
        return response()->json([
            'message' => 'Get post: ' . $id,
            'data' => [
                'id' => $id,
                'title' => 'Post ' . $id,
                'content' => 'Content of post ' . $id
            ]
        ]);
    });
}); */



// parameterized route
// required parameter
Route::get('/posts/{id}/comments/{commentId}', function ($id, $slug) {
    return response()->json([
        'message' => 'Get post: ' . $id,
        'data' => [
            'id' => $id,
            'slug' => $slug,
            'title' => 'Post ' . $id,
            'content' => 'Content of post ' . $id
        ]
    ]);
});

// optional parameter
Route::get('/users/{id?}', function ($id = null) {
    if ($id) {
        return response()->json([
            'message' => 'Get user: ' . $id,
            'data' => [
                'id' => $id,
                'name' => 'User ' . $id
            ]
        ]);
    } else {
        return response()->json([
            'message' => 'Get all users',
            'data' => [
                [
                    'id' => 1,
                    'name' => 'User 1'
                ],
                [
                    'id' => 2,
                    'name' => 'User 2'
                ]
            ]
        ]);
    }
});

Route::get('test-header', fn() => 'allowed')->middleware('custom_header');


Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {
    return $request->user()->profile();
});

Route::post('login', function(Request $request){
    $user = User::where('email', $request->email)->firstOrFail();
    $token = $user->createToken('auth_token')->plainTextToken; 
    return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
});

Route::middleware('throttle:custom')->get('limited' , function(){
    return 'not limited yet';
});

Route::apiResource('posts', PostController::class);

Route::get('user-profile', [ProfileController::class, 'index']);

Route::post('posts/{id}/comments', [PostController::class, 'addcomment']);

Route::post('users/{id}/roles', [RoleController::class, 'store']);
Route::get('roles', [RoleController::class, 'index']);

/**
 * Route: GET /user-role
 * 
 * This route checks if a user with ID 13 has the 'editor' role.
 * 
 * @return \Illuminate\Http\JsonResponse
 * - If the user does not have the 'editor' role:
 *   - HTTP Status: 403 (Forbidden)
 *   - Response: JSON object containing a message and the user's roles.
 * - If the user has the 'editor' role:
 *   - HTTP Status: 200 (OK)
 *   - Response: JSON object containing a message and the user's roles.
 * 
 * Note: Ensure that the User model and the `hasRole` method are properly defined 
 * and that the necessary role-based access control logic is implemented.
 */
Route::get('user-role', function(){
    $user = User::find(13);
    if (!$user->hasRole('editor')) {
        return response()->json([
            'message' => 'You are not authorized to edit posts.',
            'data' => $user->roles
        ], 403);
    }
    return response()->json([
        'message' => 'You are authorized to edit posts.',
        'data' => $user->roles
    ]);
});