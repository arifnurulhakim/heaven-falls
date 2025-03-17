<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdUpgradeCurrency extends Model
{
    use HasFactory;

    protected $table = 'hd_upgrade_currencies';
    protected $fillable = [
        'currency_id',
        'category',
        'weapon_id',
        'character_id',
        'level',
        'price',
    ];

    /**
     * Relasi ke model HcCurrency
     */
    public function currency()
    {
        return $this->belongsTo(HcCurrency::class, 'currency_id');
    }

    public function statWeapon()
    {
        return $this->belongsTo(HcStatWeapon::class, 'weapon_id', 'weapon_id')
                    ->whereColumn('level_reach', 'level');
    }

    /**
     * Relasi ke model HcWeapon (jika kategori adalah 'weapon')
     */
    public function weapon()
    {
        return $this->belongsTo(HcWeapon::class, 'weapon_id');
    }

    /**
     * Relasi ke model HcCharacter (jika kategori adalah 'character')
     */
    public function character()
    {
        return $this->belongsTo(HcCharacter::class, 'character_id');
    }
}
