# 🎥 Video 1 – Middleware & Sanctum Authentication

---

## 🟣 What is Middleware?

Middleware is a filter that processes HTTP requests **before** they reach your controller logic.

> 🧠 **Analogy:** Think of middleware like a **security checkpoint**.  
> Every request must pass through it before being allowed into the system.

---

## 🧪 Laravel Middleware Flow

- Middleware runs between the request and controller.
- Example:
  ```php
  Route::middleware('auth')->get('/dashboard', fn() => view('dashboard'));
  ```

- Middleware can:
  - Reject a request (e.g. unauthorized)
  - Modify request/response
  - Allow it to proceed

---

## 🔨 Creating a Custom Middleware

### 📌 Example: Add Custom Header Response
```bash
php artisan make:middleware AddCustomHeader
```

### Edit `app/Http/Middleware/AddCustomHeader.php`:
```php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Custom-Header', 'PoweredByLaravel12');

    return $response;
}

```

### Register in `app/bootstrap/app.php`:
```php
$app->routeMiddleware([
    'custom_header' => \App\Http\Middleware\AddCustomHeader::class,
]);
```

### Apply to route:
```php
Route::middleware('custom_header')->get('/custom-response', fn() => 'Allowed');
```

---

## 🔐 Sanctum – Installation & Setup

### 📦 Install Sanctum:
```bash
composer require laravel/sanctum
```

### 📦 Publish & Migrate:
```bash
php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"
php artisan migrate
```

### ✅ Add Sanctum to User Model:
```php
use Laravel\\Sanctum\\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, Notifiable, ...;
}
```

---

## 🧪 Protecting API Routes with Sanctum

### Add a secure route:
```php
Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {
    return $request->user();
});
```

# ,But ...
-----
## 🔐 Create Login Route (Very Basic)
### only get auth_token
```php
Route::post('login', function(Request $request){
    $user = User::where('email'm $request->email)->firstOrFail();
    $token = $user->createToken('auth_token')->plainTextToken;
    return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
})
```

### Test with Postman:
1. Login and get a token
2. Send request to `/api/profile` with `Authorization: Bearer <token>`
3. Without token → 401 Unauthorized
4. With token → user data 🎯

---

## 🧠 Recap

- ✅ Middleware filters every request
- ✅ You can create custom middleware for any logic (IP block, role check, etc.)
- ✅ Sanctum provides easy token-based auth for APIs
- ✅ `auth:sanctum` protects sensitive endpoints

---

👉 In the next video, we’ll explore **Laravel Passport** and compare it with Sanctum!
