<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdWeaponPlayer extends Model
{
    use HasFactory;

    protected $table = 'hd_weapon_players';

    protected $fillable = [
        'inventory_id',
        'weapon_id',
        'level',
        'created_by',
        'modified_by'
    ];

    public function inventory()
    {
        return $this->belongsTo(HrInventoryPlayer::class, 'inventory_id');
    }

    public function weapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_id');
    }
}
