
# 🎥 Video 2 – Authorization Using Policies (Laravel 12)

---

## 🧠 What is a Policy?

A **Policy** is a class that organizes authorization logic around a **specific model**.

> While Gates are used for general permission checks, Policies are tied to a specific resource – like `Post`, `User`, or `Project`.

---

## 🎯 When to Use a Policy?

Use a policy when:
- You're performing actions on Eloquent models
- You want **clean separation** of logic (e.g. who can update/delete a Post)
- You need to follow Laravel's structured, scalable approach to authorization

---

## 📦 Example Use Case: Post Management

---

## ✅ Step 1: Create a Policy for a Model

```bash
php artisan make:policy PostPolicy --model=Post
```

This creates:

```bash
app/Policies/PostPolicy.php
```

---

## ✅ Step 2: Define Permissions in the Policy

In `PostPolicy.php`:

```php
public function update(User $user, Post $post): bool
{
    return $user->id === $post->user_id;
}

public function delete(User $user, Post $post): bool
{
    return $user->id === $post->user_id || $user->is_admin;
}
```

You can define any method:
- `view`, `create`, `update`, `delete`, `forceDelete`, `restore`, etc.

---


## ✅ Step 3: Use Policy in Controller

```php
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);

    // Continue update logic
}
```

---

## 📌 Recap

- ✅ Policies are model-specific classes for organizing permission logic
- ✅ Use `php artisan make:policy --model=ModelName`
- ✅ Define your methods like `view`, `update`, `delete`
- ✅ Use `authorize()` in controllers 
- ✅ Laravel 12 auto-discovers policies if named properly

---

👉 Up next: Build your own mini RBAC system (like Spatie) from scratch!
