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
        'weapon_r_type',
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

    public function type()
    {
        return $this->belongsTo(HcTypeWeapon::class, 'weapon_r_type');
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
