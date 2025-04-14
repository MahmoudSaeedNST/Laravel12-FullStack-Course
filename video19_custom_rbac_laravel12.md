
# 🎥 Video 3 – Build Your Own Mini Role & Permission System in Laravel 12 (No Spatie)

---

## 🎯 Goal of This Lesson

- Understand how to build a simple **Role-Based Access Control (RBAC)** system without using Spatie
- Define **Roles**, **Permissions**, and **Users**
- Check permissions in a clean, reusable way
- Build this system in a way that works with Laravel 12 structure

---

## 🧱 Database Tables Overview

We’ll create the following tables:

### 1. `roles` Table
```php
$table->id();
$table->string('name')->unique();
$table->timestamps();
```

### 2. `permissions` Table
```php
$table->id();
$table->string('name')->unique();
$table->timestamps();
```

### 3. `role_user` Pivot Table
```php
$table->foreignId('user_id')->constrained()->onDelete('cascade');
$table->foreignId('role_id')->constrained()->onDelete('cascade');
```

### 4. `permission_role` Pivot Table
```php
$table->foreignId('permission_id')->constrained()->onDelete('cascade');
$table->foreignId('role_id')->constrained()->onDelete('cascade');
```

---

## 🧠 Model Relationships

### 🔸 `User.php`
```php
public function roles()
{
    return $this->belongsToMany(Role::class);
}

public function hasRole($roleName): bool
{
    return $this->roles->contains('name', $roleName);
}

public function hasPermission(string $permissionName): bool
{
    return $this->roles->contains(fn($role) => $role->hasPermission($permissionName));
}

```

---

### 🔸 `Role.php`
```php
public function permissions()
{
    return $this->belongsToMany(Permission::class);
}

public function hasPermission(string $permissionName): bool
{
    return $this->permissions->contains('name', $permissionName);
}
```

---

### 🔸 `Permission.php`
```php
public function roles()
{
    return $this->belongsToMany(Role::class);
}
```

---


---

## 💼 Example Use in Controller

```php
if (!$user->hasPermission('edit-posts')) {
    abort(403, 'You are not authorized to edit posts.');
}
```

---

## 🔧 Seed Roles and Permissions

In your Seeder:

```php
$user = User::create([
    'name' => 'Ali Laravel',
    'email' => 'ali@example.com',
    'password' => bcrypt('secret'),
]);

$adminRole = Role::create(['name' => 'admin']);
$editorRole = Role::create(['name' => 'editor']);

$editPermission = Permission::create(['name' => 'edit-posts']);
$publishPermission = Permission::create(['name' => 'publish-posts']);

$adminRole->permissions()->attach([$editPermission, $publishPermission]);
$editorRole->permissions()->attach($editPermission);

$user->roles()->attach($adminRole);
```

---

## 🎯 Why This Matters

> By building your own mini RBAC system, you deeply understand how Laravel handles:
> - Relationships
> - Permission checks
> - Middleware-style access control

Once you grasp this system, you'll be ready to use packages like Spatie when you need advanced features.

---

## 📌 Recap

- ✅ Created `roles`, `permissions`, and pivot tables
- ✅ Defined model relationships clearly
- ✅ Implemented `hasRole()` and `hasPermission()` methods
- ✅ Used both beginner-friendly and pro-style code
- ✅ Seeded test data to try everything out

---

