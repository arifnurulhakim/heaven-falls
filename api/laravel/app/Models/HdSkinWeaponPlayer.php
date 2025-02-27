<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdSkinWeaponPlayer extends Model
{
    use HasFactory;

    protected $table = 'hd_skin_weapon_players';

    protected $fillable = [
        'inventory_id',
        'skin_id',
        'skin_equipped',
        'created_by',
        'modified_by'
    ];

    // public function player()
    // {
    //     return $this->belongsTo(HdPlayer::class, 'players_id');
    // }
    public function inventory()
    {
        return $this->belongsTo(HrInventoryPlayer::class, 'inventory_id');
    }

    public function skin()
    {
        return $this->belongsTo(HrSkinWeapon::class, 'skin_id');
    }
}
