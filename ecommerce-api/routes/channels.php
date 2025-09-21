<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// user.1.orders , user.2.orders
Broadcast::channel('user.{userId}.orders', function (User $user, int $userId) {
    return $user->id === $userId; // true
});

Broadcast::channel('admin.orders', function (User $user) {
    return $user->hasRole('admin'); // true
});