<?php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('friend-invites-updates.{userId}', function () {
    return true;
});