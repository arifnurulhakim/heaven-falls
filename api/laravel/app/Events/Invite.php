<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Invite implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invites;
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Support\Collection  $invites
     * @param  int  $userId
     * @return void
     */
    public function __construct($invites, $userId)
    {
        $this->invites = $invites;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Create a private channel to broadcast the invite update
        return new PrivateChannel('user.' . $this->userId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'invites' => $this->invites,
            'user_id' => $this->userId,
        ];
    }
}
