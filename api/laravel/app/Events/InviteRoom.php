<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InviteRoom implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $name_player;
    public $room_code;
    public $friend_id;

    /**
     * Create a new event instance.
     *
     * @param  string  $name_player
     * @param  string  $room_code
     * @param  int  $friend_id
     */
    public function __construct($name_player, $room_code, $friend_id)
    {
        $this->name_player = $name_player;
        $this->room_code = $room_code;
        $this->friend_id = $friend_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Mengirim event ke channel privat berdasarkan friend_id
        return new PrivateChannel('invite-room.' . $this->friend_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message' => "{$this->name_player} has invited you to a room",
            'name_player' => $this->name_player,
            'room_code' => $this->room_code,
        ];
    }
}
