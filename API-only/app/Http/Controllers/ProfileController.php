<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProfileController extends Controller
{
    public function index()
    {
        // Simulate loading any user (example: user with ID 1)
        $user = User::with('profile', 'roles')->findOrFail(1);

        // Check if this user has 'Admin' role
        
        if(!Gate::forUser($user)->allows('admin-access')){
            return response()->json([
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        return response()->json([
            'profile' => $user->profile,
        ]);
    }

    
}
