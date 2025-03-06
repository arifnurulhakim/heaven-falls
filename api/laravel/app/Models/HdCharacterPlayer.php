<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdCharacterPlayer extends Model
{
    use HasFactory;

    protected $table = 'hd_character_players';

    protected $fillable = [
        'inventory_id',
        'character_id',
        'level',
        'created_by',
        'modified_by'
    ];

    public function inventory()
    {
        return $this->belongsTo(Player::class, 'inventory_id');
    }

    public function character()
    {
        return $this->belongsTo(HcCharacter::class, 'character_id');
    }
}
