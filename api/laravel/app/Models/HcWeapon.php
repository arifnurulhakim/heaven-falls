<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcWeapon extends Model
{
    use HasFactory;

    protected $table = 'hc_weapons';

    protected $fillable = [
        'is_active',
        'is_in_shop',
        'weapon_r_sub_type',
        'name_weapons',
        'description',
        'image',
        'level_reach',
        'attack',
        'durability',
        'accuracy',
        'recoil',
        'firespeed',
        'point_price',
        'repair_price',
        'created_by',
        'modified_by'
    ];

    public function stat()
    {
        return $this->hasMany(HcStatWeapon::class, 'weapon_id');
    }
    public function skins()
    {
        return $this->hasMany(HrSkinWeapon::class, 'weapon_id');
    }
    public function subType()
    {
        return $this->belongsTo(HcSubTypeWeapon::class, 'weapon_r_sub_type');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
