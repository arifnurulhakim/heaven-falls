<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcStates extends Model
{
    // Nama tabel di database
    protected $table = 'hc_states';

    // Primary key tabel
    protected $primaryKey = 'id';

    // Kolom-kolom yang dapat diisi melalui mass assignment
    protected $fillable = [
        'name',
        'country_id',
        'country_code',
        'fips_code',
        'iso2',
        'type',
        'level',
        'latitude',
        'longitude',
        'flag',
        'wikiDataId',
    ];

    // Kolom-kolom yang bertipe date
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    // Casting kolom ke tipe data spesifik
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'flag' => 'boolean',
    ];

    /**
     * Relasi ke country.
     */
    public function country()
    {
        return $this->belongsTo(HcCountries::class, 'country_id');
    }
}