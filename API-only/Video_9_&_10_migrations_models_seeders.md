
# Video 9 & 10 – Migrations, Seeders & Using Models with Controllers (Laravel 12)

---

## 🎯 Goal of This Stream

- Understand how to create and manage database tables using **migrations**
- Generate **models** and use them inside **controllers**
- Seed the database with **dummy/test data**
- Move from static arrays → real database-driven data

---

## 1️⃣ Creating Migrations

### 🔹 Artisan Command:

```bash
php artisan make:migration create_posts_table
```

### ✍️ Define Schema in `up()`:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content')->nullable();
    $table->timestamps();
});
```

---

## 2️⃣ Running Migrations

### 🔁 Run all migrations:
```bash
php artisan migrate
```

### ♻️ Fresh start with seeding:
```bash
php artisan migrate:fresh --seed
```

---

## 3️⃣ Creating a Model + Controller

```bash
php artisan make:model Post -mc
```

This will generate:
- `app/Models/Post.php`
- `app/Http/Controllers/PostController.php`
- Optionally migration if `-m` used

---

## 4️⃣ Using Model in Controller

### Instead of returning static data:
```php
public function index()
{
    return [
        ['id' => 1, 'title' => 'Fake Post']
    ];
}
```

### Use the Post model:
```php
use App\Models\Post;

public function index()
{
    return Post::all();
}
```

---

## 5️⃣ Creating a Factory

```bash
php artisan make:factory PostFactory --model=Post
```

### Define dummy structure:

```php
public function definition(): array
{
    return [
        'title' => fake()->sentence(),
        'content' => fake()->paragraph()
    ];
}
```

---

## 6️⃣ Creating a Seeder

```bash
php artisan make:seeder PostSeeder
```

Inside `run()` method:

```php
Post::factory()->count(20)->create();
```

Register the seeder in `DatabaseSeeder.php`:

```php
public function run()
{
    $this->call([
        PostSeeder::class,
    ]);
}
```

---

## 7️⃣ Run Everything Together

```bash
php artisan migrate:fresh --seed
```

---

## 🧪 Testing in Controller

```php
public function index()
{
    return Post::latest()->take(5)->get();
}
```

You can also return:
```php
return response()->json([
    'status' => 'success',
    'posts' => Post::paginate(10)
]);
```

---

## 📌 Recap

- ✅ Created migrations to build schema
- ✅ Generated Post model and controller
- ✅ Used factories and seeders to fill test data
- ✅ Switched from arrays to real database records
- ✅ Tested everything using API endpoints

---

👉 Up next: One-to-Many, Many-to-Many & Polymorphic relationships!
