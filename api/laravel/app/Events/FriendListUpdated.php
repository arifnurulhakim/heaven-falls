<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendListUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $friendlist;
    public $userId;

    public function __construct($friendlist, $userId)
    {
        $this->friendlist = $friendlist;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new Channel("friendlist-updates.{$this->userId}");
    }
}