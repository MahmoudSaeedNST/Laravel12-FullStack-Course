
# 🎙️ Stream 2 – Eloquent Relationships: One-to-One, One-to-Many, Many-to-Many & Polymorphic (Laravel 12)

---

## 🎯 Goal of This Stream

- Learn how to define and use **Eloquent relationships**
- Apply real-world examples using:
  - One-to-One
  - One-to-Many
  - Many-to-Many
  - Polymorphic relationships

---

## 1️⃣ One-to-One Relationship

### 🧠 Example:
- Each `User` has **one** `Profile`
- Each `Profile` belongs to **one** `User`

### 📦 Migrations:

```bash
php artisan make:model Profile -m
```

In `create_profiles_table.php`:

```php
$table->id();
$table->foreignId('user_id')->constrained()->onDelete('cascade');
$table->string('bio')->nullable();
$table->timestamps();
```

### 🔁 In Models:

**User.php**
```php
public function profile()
{
    return $this->hasOne(Profile::class);
}
```

**Profile.php**
```php
public function user()
{
    return $this->belongsTo(User::class);
}
```

### 🧪 Usage:

```php
$user = User::with('profile')->find(1);
return response()->json($user->profile->bio);
```

---

## 2️⃣ One-to-Many Relationship

### 🧠 Example:
- One `Post` has many `Comment`s
- Each `Comment` belongs to one `Post`

### 📦 Migrations:

```php
$table->foreignId('post_id')->constrained()->onDelete('cascade');
$table->text('body');
```

### 🔁 In Models:

**Post.php**
```php
public function comments()
{
    return $this->hasMany(Comment::class);
}
```

**Comment.php**
```php
public function post()
{
    return $this->belongsTo(Post::class);
}
```

### 🧪 Usage:

```php
$post = Post::with('comments')->find(1);
```

---

## 3️⃣ Many-to-Many Relationship

### 🧠 Example:
- A `User` can have many `Role`s
- A `Role` can belong to many `User`s

---

### 📘 What is a Pivot Table?

A **pivot table** is a special intermediate table used to store the **relationships** between two models in a many-to-many relationship.

#### ✅ Why we need it:
- Because there's **no direct foreign key** that can exist on either model (User or Role).
- It allows us to **store additional data** like timestamps, `assigned_by`, or `status`.

#### ❌ If we skip using a pivot:
- Laravel **won’t be able to manage** the relationship.
- You’ll have to manually create a workaround (which breaks Eloquent magic).

---

### 📦 Migration for Pivot:

```bash
php artisan make:migration create_role_user_table
```

```php
$table->foreignId('user_id')->constrained()->onDelete('cascade');
$table->foreignId('role_id')->constrained()->onDelete('cascade');
$table->primary(['user_id', 'role_id']);
```

### 🔁 In Models:

**User.php**
```php
public function roles()
{
    return $this->belongsToMany(Role::class);
}
```

**Role.php**
```php
public function users()
{
    return $this->belongsToMany(User::class);
}
```

### 🧪 Usage:

```php
$user = User::with('roles')->find(1);
$user->roles()->attach($roleId);
```

---

## 4️⃣ Polymorphic Relationships

### 🧠 Example:
- A `Comment` can belong to both a `Post` and a `Video`
- One `comments` table for both

### 📦 Migration:

```php
$table->text('body');
$table->morphs('commentable'); // adds commentable_id & commentable_type
```

### 🔁 In Models:

**Comment.php**
```php
public function commentable()
{
    return $this->morphTo();
}
```

**Post.php / Video.php**
```php
public function comments()
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

### 🧪 Usage:

```php
$post->comments()->create(['body' => 'Nice post!']);
```

---

## 📌 Recap

- ✅ **One-to-One** → e.g. User → Profile
- ✅ **One-to-Many** → e.g. Post → Comments
- ✅ **Many-to-Many** → e.g. User ↔ Roles (uses a pivot table)
- ✅ **Polymorphic** → e.g. Comment shared across Post & Video
- 🔁 **Pivot Table** = Core piece in many-to-many for relational integrity + flexibility

---

👉 Up next: Stream 3 – Eloquent vs Query Builder: Performance & Best Practices
