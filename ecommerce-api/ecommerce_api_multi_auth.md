
# Laravel 12 E-Commerce API Course
## Full Module: Multi-Auth System Implementation (Admin - Customer - Delivery)

---

## Introduction

This module walks through implementing a clean, modular multi-authentication system for three user types:
- Admin
- Customer
- Delivery

We will create:
- Type-specific authentication
- Middleware protection
- Route organization
- Simple role separation

---

#  Full Steps Breakdown

## Step 1: Update User Migration

Create a migration to add `type` to the `users` table:

```bash
php artisan make:migration add_type_to_users_table --table=users
```

Migration content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->default('customer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
```

Run the migration:

```bash
php artisan migrate
```

---

## Step 2: Update User Model

Update the `User` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'type',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->type === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->type === 'customer';
    }

    public function isDelivery(): bool
    {
        return $this->type === 'delivery';
    }
}
```

---

## Step 3: Create Authentication Controllers

(The code here is as we explained in full in the previous Video.)

---

## Step 4: Create Middleware for Type Verification

Create middlewares:

```bash
php artisan make:middleware EnsureUserIsAdmin
php artisan make:middleware EnsureUserIsCustomer
php artisan make:middleware EnsureUserIsDelivery
```

Example Admin Middleware:

```php
public function handle(Request $request, Closure $next)
{
    if (!$request->user() || !$request->user()->isAdmin()) {
        return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
    }
    return $next($request);
}
```

**Important**:  
Register the middleware manually inside `bootstrap/app.php`.

---

## Step 5: Set Up API Routes

Organize API routes:

```php
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\DeliveryAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'is.admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/profile', [AdminAuthController::class, 'profile']);
    });
});

Route::prefix('customer')->group(function () {
    Route::post('/register', [CustomerAuthController::class, 'register']);
    Route::post('/login', [CustomerAuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'is.customer'])->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/profile', [CustomerAuthController::class, 'profile']);
    });
});

Route::prefix('delivery')->group(function () {
    Route::post('/register', [DeliveryAuthController::class, 'register']);
    Route::post('/login', [DeliveryAuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'is.delivery'])->group(function () {
        Route::post('/logout', [DeliveryAuthController::class, 'logout']);
        Route::get('/profile', [DeliveryAuthController::class, 'profile']);
    });
});
```

---

## Step 6: Create Database Seeder

```bash
php artisan make:seeder UsersTableSeeder
```

```php
// database/seeders/UsersTableSeeder.php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'admin',
        ]);

        // Create Customer user
        User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'type' => 'customer',
        ]);

        // Create Delivery user
        User::create([
            'name' => 'Delivery User',
            'email' => 'delivery@example.com',
            'password' => Hash::make('password123'),
            'type' => 'delivery',
        ]);
    }
}
```

---

# ✅ Practice Exercise

Extend DeliveryAuthController to allow updating profile.

---

# 🎯 End of Multi-Auth Full Module
