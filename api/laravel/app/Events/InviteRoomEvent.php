<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class InviteRoomEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $data;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($data, $userId)
    {
        $this->data = $data;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new PrivateChannel('invite-room.' . $this->userId);
    }

    /**
     * Get the broadcasted data.
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->data['message'],
            'name_player' => $this->data['name_player'],
            'room_code' => $this->data['room_code'],
        ];
    }

    /**
     * Nama event broadcast.
     */
    public function broadcastAs()
    {
        return 'invite-room-event';
    }
}
