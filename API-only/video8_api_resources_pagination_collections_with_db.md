
# 🎥 Video 8 – Pagination, Collections & API Resources in Laravel 12

---

## 🔄 Why Use API Resources?

Laravel API Resources help you **format API responses cleanly and consistently**.

> 🧠 Instead of returning raw models, you return well-structured JSON tailored to frontend needs.

---

## 📦 Create a Resource Class

```bash
php artisan make:resource PostResource
```

This creates:
```php
App\Http\Resources\PostResource
```

---

## 🧪 Using Resource in Controller

In your controller:

```php
use App\Http\Resources\PostResource;

public function show(Post $post)
{
    return new PostResource($post);
}
```

Will return:
```json
{
  "data": {
    "id": 1,
    "title": "Post Title",
    "content": "Post Content"
  }
}
```

---

## 🛠️ Customize Resource Format

Inside `PostResource.php`:

```php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'summary' => Str::limit($this->content, 100),
        'created_at' => $this->created_at->toDateString(),
    ];
}
```

---

## 🔁 Collections of Resources

In `index()` method:

```php
public function index()
{
    return PostResource::collection(Post::latest()->get());
}
```

This wraps each post in a resource format.

---

## 📄 Adding Pagination

```php
public function index()
{
    return PostResource::collection(Post::paginate(10));
}
```

Now the response includes:

- `data`: List of resources
- `meta`: Pagination info
- `links`: Pagination URLs

---

## 🔎 Example Output (Paginated)

```json
{
  "data": [
    { "id": 1, "title": "Post 1" },
    { "id": 2, "title": "Post 2" }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

---

## 📌 Recap

- ✅ Use `make:resource` to build clean API layers
- ✅ `PostResource::collection()` wraps all items
- ✅ Pagination adds metadata and links automatically
- ✅ Customize fields returned to keep frontend clean and secure

---

👉 Up next: Uploading files & handling media in Laravel APIs!
