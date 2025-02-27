<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcSubTypeWeapon extends Model
{
    use HasFactory;

    protected $table = 'hc_sub_type_weapons';

    protected $fillable = [
        'name',
        'type_weapon_id',
        'created_by',
        'modified_by'
    ];
    public function type()
    {
        return $this->belongsTo(HcTypeWeapon::class, 'type_weapon_id');
    }
    public function weapon()
    {
        return $this->hasMany(HcWeapon::class, 'weapon_r_sub_type');
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
