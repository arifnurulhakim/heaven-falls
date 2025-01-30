<?php

namespace App\Events;

use App\Models\HdFriendList;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class FriendInvitesUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $invites;
    public $userId;

    public function __construct($invites, $userId)
    {
        $this->invites = $invites; // Menyimpan data pertemanan yang diperbarui
        $this->userId = $userId;   // Menyimpan ID pengguna yang mengundang
    }

    /**
     * The channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('friend-invites-updates.' . $this->userId); // Channel berdasarkan ID user
    }

    /**
     * Event data yang akan disiarkan ke frontend.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Kirimkan data yang relevan, seperti daftar undangan yang diperbarui
        return [
            'invites' => $this->invites,  // Data pertemanan yang diperbarui
            'userId' => $this->userId,    // ID pengguna yang mengundang
        ];
    }

    /**
     * Nama event yang akan digunakan oleh Pusher.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'FriendInvitesUpdated';  // Nama event yang diterima di frontend
    }
}