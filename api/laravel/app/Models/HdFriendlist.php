<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdFriendlist extends Model
{
    use HasFactory;
    protected $table = 'hd_friend_lists';
    protected $fillable = [
        'player_id',
        'friend_id',
        'lobby_code',
        'created_by',
        'modified_by',
        'invited',
        'accepted',
        'ignored',
        'blocked_by',
        'removed',
    ];

    // The attributes that should be cast to native types
    protected $casts = [
        'invited' => 'boolean',
        'accepted' => 'boolean',
        'ignored' => 'boolean',
        'removed' => 'boolean',
        'blocked_by' => 'integer', // Can be null, so integer type
    ];
}
