<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdSkinCharacterPlayer extends Model
{
    use HasFactory;

    protected $table = 'hd_skin_character_players';

    protected $fillable = [
        'inventory_id',
        'skin_id',
        'character_id',
        'skin_equipped',
        // 'skin_status',
        'created_by',
        'modified_by'
    ];

    // public function player()
    // {
    //     return $this->belongsTo(HdPlayer::class, 'players_id');
    // }
    public function inventory()
    {
        return $this->belongsTo(Player::class, 'inventory_id');
    }
    public function character()
    {
        return $this->belongsTo(HcCharacter::class, 'character_id');
    }

    public function skin()
    {
        return $this->belongsTo(HrSkinCharacter::class, 'skin_id');
    }
}
