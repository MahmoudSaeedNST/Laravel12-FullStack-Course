<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{userId}.orders', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

broadcast::channel('admin.orders', function ($user) {
    return $user->hasRole('admin');
});