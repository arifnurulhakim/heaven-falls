<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrBattlepassPurchase extends Model
{
    use HasFactory;

    protected $table = 'hr_battlepass_purchase';
    public $timestamps = false;
    protected $fillable = [
        'player_id',
        'battlepass_id',
        'purchased_at',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function battlepass()
    {
        return $this->belongsTo(HdBattlepass::class, 'battlepass_id');
    }
}
