<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Friendlist implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $playerData;

    /**
     * Create a new event instance.
     */
    public function __construct($playerData)
    {
        $this->playerData = $playerData->toArray(); // Pastikan data dalam format array
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('friendlist');
    }

    /**
     * Data yang dikirim ke frontend.
     */
    public function broadcastWith()
    {
        return [
            'data' => $this->playerData
        ];
    }
}
