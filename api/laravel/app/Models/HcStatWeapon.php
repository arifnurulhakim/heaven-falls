<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcStatWeapon extends Model
{
    use HasFactory;

    protected $table = 'hc_stat_weapons';

    protected $fillable = [
        'weapon_id',
        'level_reach',
        'accuracy',
        'damage',
        'range',
        'fire_rate',
        'created_by',
        'modified_by'
    ];

    public function weapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_id');
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
