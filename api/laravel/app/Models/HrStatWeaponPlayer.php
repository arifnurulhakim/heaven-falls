<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrStatWeaponPlayer extends Model
{
    use HasFactory;

    protected $table = 'hr_stat_weapon_players';

    protected $fillable = [
        'player_id',
        'weapon_id',
        'level'
    ];

    /**
     * Relationship to the Player model.
     */
    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    /**
     * Relationship to the Weapon model.
     */
    public function weapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_id');
    }
}
