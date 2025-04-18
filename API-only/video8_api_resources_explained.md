
# 🎥 Video 10 – API Resources & Collections (No Database Yet)

---

## 🧠 What Are Laravel API Resources?

**Laravel API Resources** are classes used to transform and shape your data before sending it back as a JSON response from your API.

> Think of them as a "presentation layer" between your backend logic and the API consumer (e.g., frontend/mobile app).

You use them when:
- You want to return consistent JSON response structure
- You want to **hide**, **format**, or **rename** fields before exposing them
- You want to add computed or extra fields in your response

---

## 📦 Creating a Resource

```bash
php artisan make:resource PostResource
```

This creates:

```
app/Http/Resources/PostResource.php
```

---

## 📥 Example 1: Using Resource for a Single Item (Static Data)

### 📂 Controller

```php
use App\Http\Resources\PostResource;

public function show()
{
    $post = (object)[
        'id' => 1,
        'title' => 'Welcome to API Resources',
        'content' => 'This is a static post used to demonstrate Laravel resources.'
    ];

    return new PostResource($post);
}
```

---

### 🛠️ In `PostResource.php`

```php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'summary' => substr($this->content, 0, 50) . '...',
        'length' => strlen($this->content)
    ];
}
```

---

### 🧪 Output JSON:

```json
{
  "data": {
    "id": 1,
    "title": "Welcome to API Resources",
    "summary": "This is a static post used to demonstrate La...",
    "length": 67
  }
}
```

✅ Clean, formatted, and safe to expose to frontend.

---

## 🔁 What Are Resource Collections?

A **Resource Collection** is a wrapper around a list of items.  
Laravel automatically wraps multiple items using the same Resource class.

---

## 📥 Example 2: Collection with Dummy Data

### 📂 Controller

```php
use App\Http\Resources\PostResource;

public function index()
{
    $posts = collect([
        ['id' => 1, 'title' => 'First', 'content' => 'First content here...'],
        ['id' => 2, 'title' => 'Second', 'content' => 'Second content here...']
    ])->map(fn($post) => (object) $post);

    return PostResource::collection($posts);
}
```

---

### 🧪 Output JSON

```json
{
  "data": [
    {
      "id": 1,
      "title": "First",
      "summary": "First content here...",
      "length": 21
    },
    {
      "id": 2,
      "title": "Second",
      "summary": "Second content here...",
      "length": 23
    }
  ]
}
```

---

## 🤔 Why Use Resources and Collections?

| Benefit                          | Resource (Single) | Collection (Multiple) |
|----------------------------------|-------------------|------------------------|
| Clean & consistent formatting    | ✅                | ✅                     |
| Custom field formatting          | ✅                | ✅                     |
| Control exposed data             | ✅                | ✅                     |
| Can include computed fields      | ✅                | ✅                     |
| Works well with pagination       | ❌                | ✅                     |

> When returning multiple records (especially paginated), always use collections.

---

## 📝 Recap

- ✅ API Resources = Format how your API returns data
- ✅ You can use them without a database (with dummy/static data)
- ✅ Use `.collection()` when returning lists
- ✅ Customize `toArray()` to control your API shape
- ✅ Perfect stepping stone before using real Models & Eloquent

---

👉 In the next video: connect API Resources to real Models using Eloquent!
