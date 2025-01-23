<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdWallet extends Model
{
    use HasFactory;

    protected $table = 'hd_wallets';

    protected $fillable = [
        'player_id',
        'currency_id',
        'amount',
        'category',
        'label',
        'created_by',
        'modified_by'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function currency()
    {
        return $this->belongsTo(HcCurrency::class, 'currency_id');
    }
}
