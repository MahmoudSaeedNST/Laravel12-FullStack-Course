# Module 2: Role-Based Authorization with Spatie Permission

Now that we have successfully built a Multi-Auth system in our E-Commerce API project, it's time to take it a step further. In this module, we'll implement **fine-grained role and permission control** using the powerful `spatie/laravel-permission` package.

By the end of this module, you will be able to control exactly **what each user role is allowed to do** within the system, not just who they are.


---

# Step 1: Installing Spatie Permission

First, let's install the Spatie Permission package via Composer:

```bash
composer require spatie/laravel-permission
```

Next, publish the configuration and migration files:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Then, migrate your database to create the necessary tables:

```bash
php artisan migrate
```

---

# Step 2: Updating the User Model

We need to update our existing `User` model to enable role and permission management.

```php
// app/Models/User.php

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

}
```

---

# Step 3: Creating Roles and Permissions Seeder

Now let's define the roles and permissions using a database seeder.

Create a new seeder:

```bash
php artisan make:seeder RolesAndPermissionsSeeder
```

Then edit the generated seeder file:

```php
// database/seeders/RolesAndPermissionsSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define product permissions
        Permission::create(['name' => 'view products']);
        Permission::create(['name' => 'create products']);
        Permission::create(['name' => 'edit products']);
        Permission::create(['name' => 'delete products']);

        // Define order permissions
        Permission::create(['name' => 'view orders']);
        Permission::create(['name' => 'create orders']);
        Permission::create(['name' => 'update orders']);
        Permission::create(['name' => 'cancel orders']);

        // Define user permissions
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'edit users']);

        // Define delivery permissions
        Permission::create(['name' => 'view deliveries']);
        Permission::create(['name' => 'update delivery status']);

        // Create Admin role and assign all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view orders',
            'update orders',
            'cancel orders',
            'view users',
            'edit users',
            'view deliveries',
        ]);

        // Create Customer role with limited permissions
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'view products',
            'view orders',
            'create order',
            'cancel orders'
        ]);

        // Create Delivery role
        $deliveryRole = Role::create(['name' => 'delivery']);
        $deliveryRole->givePermissionTo([
            'view deliveries',
            'update delivery status',
            'view orders',
            'view products'
        ]);
    }
}
```

Now update your main database seeder to call this seeder:

```php
// database/seeders/DatabaseSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersTableSeeder::class,
        ]);
    }
}
```

---

# Step 4: Creating Permission Middleware

Create a new custom middleware to check user permissions:

```bash
php artisan make:middleware CheckPermission
```

Edit the middleware:

```php
// app/Http/Middleware/CheckPermission.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (! $request->user()) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (! $request->user()->can($permission)) {
            return response()->json([
                'message' => 'You do not have permission to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
```

Now register the middleware alias in `bootstrap/app.php`:

```php
// bootstrap/app.php
[
    // Other middleware...
    'permission' => \App\Http\Middleware\CheckPermission::class,
];
```

---

# Step 5: Protecting Routes with Permission Middleware

Use the permission middleware directly in your route definitions:

```php
// routes/api.php

Route::prefix('admin')->middleware(['auth:sanctum', 'is.admin'])->group(function () {

    Route::middleware('permission:view products')->get('/products', [ProductController::class, 'index']);
    Route::middleware('permission:create products')->post('/products', [ProductController::class, 'store']);
    Route::middleware('permission:edit products')->put('/products/{product}', [ProductController::class, 'update']);
    Route::middleware('permission:delete products')->delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::middleware('permission:view users')->get('/users', [AdminUserController::class, 'index']);
    Route::middleware('permission:edit users')->put('/users/{user}', [AdminUserController::class, 'update']);

});
```

This ensures that **only users with the specific permissions** can access the endpoints.

---

# 🎯 Conclusion for Part 1

✅ Now you know how to:
- Install and configure Spatie Permission.
- Define and seed roles and permissions.
- Protect routes using permission middleware.

In Part 2, we will:
- Show how to manually check permissions inside controllers.
- Build a `PromoteToSuperAdmin` Artisan command.
- Automatically promote a user to Super Admin based on the `.env` file.

Stay tuned 🚀!
