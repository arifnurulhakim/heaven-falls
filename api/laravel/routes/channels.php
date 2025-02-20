<?php

// routes/channels.php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
Broadcast::channel('friendlist', function ($user) {
    return Auth::check(); // Hanya user yang sudah login yang bisa mendengarkan channel ini
});
