<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrCurrenciesShop extends Model
{
    use HasFactory;

    protected $table = 'hr_currencies_shops';

    protected $fillable = [
        'currency_id',
        'name',
        'desciption',
        'value',
        'created_by',
        'modified_by'
    ];

    public function currency()
    {
        return $this->belongsTo(HcCurrency::class, 'currency_id');
    }
}
