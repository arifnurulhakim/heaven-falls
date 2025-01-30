<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HcCountries extends Model
{
    // Nama tabel di database
    protected $table = 'hc_countries';

    // Primary key tabel
    protected $primaryKey = 'id';

    // Kolom-kolom yang dapat diisi melalui mass assignment
    protected $fillable = [
        'name',
        'iso3',
        'numeric_code',
        'iso2',
        'phonecode',
        'capital',
        'currency',
        'currency_name',
        'currency_symbol',
        'tld',
        'native',
        'nationality',
        'timezones',
        'translations',
        'latitude',
        'longitude',
        'emoji',
        'emojiU',
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
        'timezones' => 'array', // Jika timezones disimpan sebagai JSON
        'translations' => 'array', // Jika translations disimpan sebagai JSON
    ];

  
}