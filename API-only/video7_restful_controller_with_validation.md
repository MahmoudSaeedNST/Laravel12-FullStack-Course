
# 🎥 Video 7 – Creating RESTful Controllers, Route & Validating API Requests

---

## 🧠 What are RESTful Controllers?

RESTful Controllers in Laravel allow you to create organized, clean API endpoints for CRUD operations:
- **index()** → Get all records
- **store()** → Save new record
- **show()** → Get one record
- **update()** → Update existing record
- **destroy()** → Delete record

---

## 🛠️ Generate a RESTful Controller

```bash
php artisan make:controller PostController --api
```

This creates a controller with methods for API use only (no `create` or `edit`).

---

## 🔁 Define API Resource Routes

In `routes/api.php`:

```php
Route::apiResource('posts', PostController::class);
```

Laravel will auto-generate:
| Verb   | URI             | Action   | Controller Method         |
|--------|------------------|----------|---------------------------|
| GET    | /api/posts       | index    | PostController@index      |
| POST   | /api/posts       | store    | PostController@store      |
| GET    | /api/posts/{id}  | show     | PostController@show       |
| PUT    | /api/posts/{id}  | update   | PostController@update     |
| DELETE | /api/posts/{id}  | destroy  | PostController@destroy    |

---

## 🔗 Route Model Binding – What is it?

Laravel automatically injects Eloquent models into your controller methods based on route parameters.

### 🔸 Example:

```php
Route::get('/posts/{post}', [PostController::class, 'show']);
```

In `PostController`:

```php
public function show(Post $post)
{
    return response()->json($post);
}
```

✅ No need to manually use `Post::find($id)`  
Laravel resolves the `{post}` parameter into a real Post model by ID.

---

## 🧩 Customizing Route Key (Optional)

If you want to resolve models by slug instead of ID:

In `Post.php` model:

```php
public function getRouteKeyName()
{
    return 'slug';
}
```

Now `{post}` in the route will match by `slug`.

---

## ✅ Validating Requests with FormRequest

Instead of writing validation logic inside the controller, use a custom request class.

```bash
php artisan make:request StorePostRequest
```

In `StorePostRequest.php`:

```php
public function rules()
{
    return [
        'title' => 'required|string|max:255',
        'content' => 'required|string|min:10',
    ];
}
```

In `PostController`:

```php
public function store(StorePostRequest $request)
{
    $validated = $request->validated();
    
    $post = Post::create($validated);

    return response()->json([
        'status' => 'success',
        'message' => 'Post created successfully.',
        'data' => $post,
    ], 201);
}
```

Laravel will return validation errors automatically in this format:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

---

## 📌 Recap

- ✅ Use `--api` flag to generate clean RESTful controllers
- ✅ `apiResource` generates all routes automatically
- ✅ Route Model Binding injects model directly into controller
- ✅ You can customize binding using `getRouteKeyName()`
- ✅ Use FormRequest for clean validation
- ✅ Return consistent JSON responses for success and validation errors

---

👉 Up next: Pagination, Collections, and working with API Resources in Laravel 12!
