<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdWeaponPlayer extends Model
{
    use HasFactory;

    protected $table = 'hd_character_players';

    protected $fillable = [
        'player_id',
        'character_id',
        'created_by',
        'modified_by'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function character()
    {
        return $this->belongsTo(HcCharacter::class, 'character_id');
    }
}
