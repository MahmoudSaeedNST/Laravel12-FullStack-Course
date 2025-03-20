# 📘 Defining API Routes – `routes/api.php`

---

## 🟣 What Are API Routes?
API routes are endpoints your frontend or mobile app can call to interact with your Laravel backend.

> 🧠 **Analogy:** "Waiters in a restaurant – they take your order (request) and bring you food (response)."

---

## 🛠️ Where to Define API Routes
- API routes go in:
  ```php
  routes/api.php
  ```

- Laravel auto-loads this file through:
  ```php
  bootstrap/app.php
  ->withRouting(
    api: __DIR__.'/../routes/api.php',
    apiPrefix: 'api/admin',
    // ...
    )
  ```


---

## 🧪 Basic Example
```php
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});
```

Test in Postman:  
`GET http://yourdomain.com/api/ping`

---

## ⚙️ Types of Routes

| Method | Description           | Example                                          |
|--------|-----------------------|--------------------------------------------------|
| GET    | Fetch data            | `Route::get('/posts', fn() => ...);`             |
| POST   | Submit new data       | `Route::post('/posts', fn() => ...);`            |
| PUT    | Update existing data  | `Route::put('/posts/{id}', fn($id) => ...);`     |
| DELETE | Delete data           | `Route::delete('/posts/{id}', fn($id) => ...);`  |

---

## 🧩 Parameterized Routes

**Required parameter:**
```php
Route::get('/users/{id}', function ($id) {
    return response()->json(['user_id' => $id]);
});
```

**Optional parameter:**
```php
Route::get('/users/{id?}', function ($id = null) {
    return $id ? "User: $id" : "No user ID provided";
});
```

---

## 🔄 Route Grouping (Preview)
```php
Route::prefix('v1')->group(function () {
    Route::get('/posts', fn() => ...);
    Route::post('/posts', fn() => ...);
});
```

---

## 📌 Recap
- `routes/api.php` is for external clients (React, mobile, etc.)
- You can use GET, POST, PUT, DELETE directly without controllers
- Parameterized routes let you pass dynamic data
- `php artisan route:list`

