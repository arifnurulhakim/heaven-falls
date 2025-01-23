<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrStatCharacterPlayer extends Model
{
    use HasFactory;

    protected $table = 'hr_stat_character_players';

    protected $fillable = [
        'player_id',
        'character_id',
        'hitpoints',
        'damage',
        'defense',
        'speed',
    ];

    /**
     * Relationship to the Player model.
     */
    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    /**
     * Relationship to the Character model.
     */
    public function character()
    {
        return $this->belongsTo(HcCharacter::class, 'character_id');
    }
}
