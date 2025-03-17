<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrSkinWeapon extends Model
{
    use HasFactory;

    protected $table = 'hr_skin_weapons';

    protected $fillable = [
        'weapon_id',
        'name_skin',
        'level_reach',
        'code_skin',
        'image_skin',
        'point_price',
        'created_by',
        'modified_by'
    ];

    public function weapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_id');
    }

    public function skinPlayer()
    {
        return $this->hasMany(HdSkinWeaponPlayer::class, 'skin_id');
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
