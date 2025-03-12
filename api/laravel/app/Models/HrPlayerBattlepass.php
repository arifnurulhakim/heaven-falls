<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPlayerBattlepass extends Model
{
    use HasFactory;

    protected $table = 'hr_player_battlepass';
    public $timestamps = false;
    protected $fillable = [
        'battlepass_id',
        'player_id',
        'status_claimed',
        'status_claimed_premium',
    ];

    public function battlepass()
    {
        return $this->belongsTo(HdBattlepass::class, 'battlepass_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
