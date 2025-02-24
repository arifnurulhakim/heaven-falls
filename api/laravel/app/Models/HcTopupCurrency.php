<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcTopupCurrency extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'hc_topup_currencies';

    protected $fillable = ['topup_id', 'currency_id', 'price_topup'];

    public function currency()
    {
        return $this->belongsTo(HcCurrency::class, 'currency_id');
    }
    public function topup()
    {
        return $this->belongsTo(HcTopup::class, 'topup_id');
    }

}
