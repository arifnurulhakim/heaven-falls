<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrSubscriptionPurchase extends Model
{
    use HasFactory;

    protected $table = 'hr_subscription_purchase';
    public $timestamps = false;
    protected $fillable = [
        'player_id',
        'subscription_id',
        'purchased_at',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function subscription()
    {
        return $this->belongsTo(HdSubscription::class, 'subscription_id');
    }
}
