<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    //register
    public function register(Request $request)
    {
        // Validate the request
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Hash the password
        $data['password'] = Hash::make($data['password']);
        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'type' => 'customer', // set the user type to customer
        ]);

        // generate a token
        $token = $user->createToken('auth_token')->plainTextToken;

        // assing user role to customer 
        $user->assignRole('customer');
        
        return response()->json(['message' => 'User registered successfully', 'user' => $user, 'token' => $token], 201);
    }

    //login
    public function login(Request $request)
    {
        // validate the request
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);
        // check if the user exists
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        // check if the password is correct
        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // generate a token
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['message' => 'User logged in successfully', 'user' => $user, 'token' => $token], 200);
    }

    // logout
    public function logout(Request $request)
    {
        // revoke the token
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'User logged out successfully'], 200);
    }

    // get user (profile) or me
    public function me(Request $request)
    {
        // return the user

        return response()->json(['user' => $request->user()], 200);
    }
    // get accss token
    public function getAccessToken(Request $request)
    {
        // return the access token
        return response()->json(['token' => $request->user()->currentAccessToken()], 200);
    }
}
