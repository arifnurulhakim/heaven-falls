<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcTopup extends Model
{
    use HasFactory;

    protected $table = 'hc_topup';

    protected $fillable = [
        'is_active',
        'is_in_shop',
        'name_topup',
        'product_code',
        'description',
        'image',
        'amount',
        'currency_id',
        'created_by',
        'modified_by',
    ];

    // Currency yang akan diterima
    public function currency()
    {
        return $this->belongsTo(HcCurrency::class, 'currency_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    // Currency yang bisa digunakan untuk membeli
    public function topupCurrencies()
    {
        return $this->hasMany(HcTopupCurrency::class, 'topup_id')->with('currency');
    }
}
