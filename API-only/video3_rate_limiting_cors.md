
# 🎥 Video 3 – Rate Limiting & CORS Setup in Laravel 12

---

## 🛡️ Why Rate Limiting?

> 🧠 **Analogy (Real-Life):**  
>Think of Rate Limiting like an ATM.
>If you enter the wrong PIN too many times in a short period, the machine locks you out temporarily to protect your account.



Same with Laravel: if a user or bot sends too many requests too fast, the app limits or blocks them temporarily.

### ✅ Real Use Case:
- Someone hits your `/login` route 100 times/second (bot attack).
- Laravel returns `429 Too Many Requests` to prevent abuse or server overload.

---

## 🚦 Basic Rate Limiting in Laravel 12

Laravel provides the `throttle` middleware by default.

```php
Route::middleware('throttle:5,1')->get('/limited', function () {
    return 'You are not blocked (yet)';
});
```

- This means: 5 requests per **minute** allowed from the same IP.
- 6th request → blocked with HTTP `429`.

---

## 🧪 Customize Rate Limiting

In `App\Providers\CustomRouteServiceProvider.php`, define custom rules:
First you need to create that in by `php artisan make:provider CustomRouteServiceProvider` 
and then register it in the `bootstrap\provider.php`

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot()
{
    RateLimiter::for('custom', function (Request $request) {
        return Limit::perMinute(10)->by($request->ip());
    });
}
```

Apply it to routes:

```php
Route::middleware('throttle:custom')->get('/custom', fn() => 'Custom Rate');
```

---

## 🧩 Use Case: Role-Based Rate Limiting

Premium users get 100 requests/min, Free users get 10:

```php
RateLimiter::for('api', function (Request $request) {
    return $request->user() && $request->user()->is_premium
        ? Limit::perMinute(100)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});
```

---

## 🌍 What is CORS?

CORS = Cross-Origin Resource Sharing  
It controls which **origins (websites/domains)** can send requests to your Laravel app.

> 🧠 **Analogy:**  
> Think of CORS like a receptionist of your Office – she checks where you're from (origin), and only lets you in if you're allowed.

---

## ⚙️ Setting up CORS in Laravel 12

CORS is configured via Laravel’s Command `php artisan config:publish cors` support in `routes/api.php`  
👉 Laravel uses `HandleCors` middleware automatically if defined in:

```php
config/cors.php
```

Example:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

> ✅ Match frontend domain exactly (e.g., React or Vue dev server)

---

## 🔐 Securing CORS in Production

- In production, don’t use `['*']` for `allowed_origins`
- Instead, specify allowed domains (e.g., `https://yourfrontend.com`)

---

## 🧪 Test CORS Setup

From frontend (React or Vue), try calling:

```http
http://127.0.0.1:8000/api/secure-route
```

If CORS fails:
```
CORS policy: No 'Access-Control-Allow-Origin' header is present
```

If CORS passes:
✅ Browser accepts the request

---

## 📌 Recap

- ✅ Use Laravel’s `throttle` middleware to stop request abuse
- ✅ Customize per IP, user, or even user roles
- ✅ Laravel 12 has built-in CORS support via `config/cors.php`
- ✅ Always match allowed origins for frontend dev/testing

---

👉 Up next: Implement login & auth flow across CORS with Sanctum or Passport!
