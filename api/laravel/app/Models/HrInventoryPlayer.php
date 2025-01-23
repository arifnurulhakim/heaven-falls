<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrInventoryPlayer extends Model
{
    use HasFactory;

    protected $table = 'hr_inventory_players';

    protected $fillable = [
        'weapon_primary_r_id',
        'weapon_secondary_r_id',
        'weapon_melee_r_id',
        'weapon_explosive_r_id',
        'created_by',
        'modified_by'
    ];

    public function player()
    {
        return $this->hasOne(Player::class, 'inventory_r_id');
    }

    public function primaryWeapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_primary_r_id');
    }

    public function secondaryWeapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_secondary_r_id');
    }

    public function meleeWeapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_melee_r_id');
    }

    public function explosiveWeapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_explosive_r_id');
    }
}
