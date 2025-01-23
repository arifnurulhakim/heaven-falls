<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrLevelPlayersLog extends Model
{
    use HasFactory;

    protected $table = 'hr_level_players_log';

    protected $fillable = [
        'level_player_id',
        'exp',
    ];

    // Define the relationship if `level_player_id` relates to another model
    public function levelPlayer()
    {
        return $this->belongsTo(LevelPlayer::class, 'level_player_id'); // Replace with actual model if needed
    }
}
