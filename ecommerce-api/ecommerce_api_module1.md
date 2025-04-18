
# 🚀 Laravel 12 E-Commerce RESTful API | Full Project 
---

# 📦 Module 1: Setup and Authentication System

## 🎥 Video 01 – Laravel 12 API Auth System with Sanctum

---

## 🛠️ Project Initialization

### ✅ Step 1: Create a New Laravel Project
```bash
composer create-project laravel/laravel laravel-ecommerce
```

> We start fresh so we can walk through everything step-by-step and match Laravel 12 structure.

---

### ✅ Step 2: Navigate into the Project
```bash
cd laravel-ecommerce
```

---

### ✅ Step 3: Publish Sanctum Config (optional in Laravel 12)
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

> Allows you to customize Sanctum behavior like expiration, guards, etc.

---

### ✅ Step 4: Run Migrations
```bash
php artisan migrate
```

> Sanctum needs a table to store API tokens. This sets it up.

---

## 🔐 Build Auth System

### ✅ Step 5: Create AuthController
```bash
php artisan make:controller Api/AuthController
```

> All authentication logic will live here in API namespace.

---

### ✅ Step 6: AuthController Logic

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
```

---

### ✅ Step 7: Setup API Routes

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
});
```

---

## 📌 Recap of Module 1 - Part 1

- ✅ Fresh Laravel 12 setup
- ✅ Sanctum API token auth installed
- ✅ Built full auth system: Register, Login, Logout, Profile
- ✅ Project structure is ready for next steps

---

**Next:** We’ll start implementing Role-Based Access Control (RBAC) using Spatie or a custom approach 🔐

