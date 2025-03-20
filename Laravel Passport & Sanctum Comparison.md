# 🎥 Video 2 – Laravel Passport & Sanctum Comparison

---

## 🔑 What is Laravel Passport?

Laravel Passport provides a full **OAuth2** server implementation that allows your Laravel application to issue **access tokens**, **refresh tokens**, and handle **OAuth clients**.

> 🧠 **Real Use Case:**  
> You're building an API that will be accessed by:
> - A mobile app
> - Third-party developers
> - Frontend apps hosted on separate domains (React, Vue, etc.)

In this case, **you need full OAuth support** with scopes, clients, and refresh tokens.

---

## 🧩 When to Use Passport?

| Feature              | Sanctum                             | Passport                            |
|----------------------|-------------------------------------|--------------------------------------|
| Simplicity           | ✅ Super easy to install & use       | ❌ Complex – OAuth2 flow required     |
| Token Types          | Simple bearer tokens                | Access + Refresh + Personal tokens   |
| Ideal For            | Internal apps (SPAs, mobile)        | Public APIs, third-party clients     |
| Scopes/Clients       | ❌ Not available                    | ✅ Scopes, Clients, Full OAuth2       |
| Token Revocation     | ❌ Manual only                      | ✅ Built-in OAuth2 revocation         |
| Security             | Basic API token system              | Full OAuth-compliant flow            |

---

## ⚙️ Installing Passport

now provides an easy scaffolding command to install Passport:

```bash
php artisan install:api --passport
```

This command will:
- Install Passport
- Run required migrations
- Register default routes for token issuing

> ⚠️ You no longer need to manually register `Passport::routes()` in Laravel 12.

---

## ⚙️ Setting Up Passport 

### ✅ Update `User.php`:

```php
use Laravel\\Passport\\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, Notifiable, ...;
}
```

> This allows the user model to issue access tokens via Passport.

---

### ✅ Update `config/auth.php`:

Make sure the `api` guard uses `passport`:

```php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

---

## ✅ Protecting Routes with Passport

```php
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
```

> You must pass the token using `Authorization: Bearer <token>` in your headers.

---

## 🧪 Testing Passport in Postman

1. Call `/oauth/token` with your client credentials and user email/password to get an access token.
2. Use `Authorization: Bearer <access_token>` to access protected routes.
3. Use `/oauth/clients` to manage clients (requires authorization).
4. Use `/oauth/scopes` to limit token access.

---

## 🧠 Real Case: When Should I Use Passport?

- You're building a **developer-friendly API** where external devs can register OAuth clients.
- You want to allow multiple frontends (e.g. web + mobile) with **refresh tokens** and session handling.
- You need **fine-grained permissions** using `scopes`.

```php
Passport::tokensCan([
    'read-posts' => 'Read user posts',
    'write-posts' => 'Create new posts',
]);

Passport::setDefaultScope([
    'read-posts',
]);
```

---

## 🤔 Sanctum vs Passport Summary

| Feature             | Sanctum                        | Passport (OAuth2)               |
|---------------------|---------------------------------|----------------------------------|
| Use Case            | Internal SPAs / APIs            | Public APIs / External Clients  |
| Token Lifetime      | Static or manually controlled   | Refresh tokens + expiration     |
| Scopes/Permissions  | ❌ Not supported                | ✅ Yes                          |
| Installation        | Very light setup                | Full OAuth2 infrastructure       |
| Ideal for           | Projects you fully control      | Projects exposed to external devs|

---

## 🧠 Final Recap

- ✅ Sanctum = Quick, secure, lightweight API authentication
- ✅ Passport = OAuth2-compliant, ideal for public-facing or multi-client APIs
- ✅ Choose based on your **project complexity and audience**
- ❗ Don't mix Sanctum and Passport unless you know exactly what you're doing

---
 
🚀 Up next: **Role-based permissions**, **real-time APIs**, or **multi-auth systems**? Your call!
