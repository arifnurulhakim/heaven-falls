<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcTypeWeapon extends Model
{
    use HasFactory;

    protected $table = 'hc_type_weapons';

    protected $fillable = [
        'name',
        'created_by',
        'modified_by'
    ];
    public function subType()
    {
        return $this->hasMany(HcSubTypeWeapon::class, 'type_weapon_id');
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
