<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrProgressBattlepass extends Model
{
    use HasFactory;

    protected $table = 'hr_progress_battlepass';
    public $timestamps = false;
    protected $fillable = [
        'player_id',
        'quest_battlepass_id',
        'current_progress',
        'is_completed',
        'updated_at',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function quest()
    {
        return $this->belongsTo(HcQuestBattlepass::class, 'quest_battlepass_id');
    }
}
