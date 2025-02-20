<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPlayerLastSeen extends Model
{
    use HasFactory;

    protected $table = 'hr_player_last_seens';

    protected $fillable = [
        'player_id',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    /**
     * Relasi ke model Player
     */
    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
