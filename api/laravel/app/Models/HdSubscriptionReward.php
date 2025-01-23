<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdSubscriptionReward extends Model
{
    use HasFactory;

    protected $table = 'hd_subscription_rewards';
    public $timestamps = false;
    protected $fillable = [
        'subscription_id',
        'reward_id',

    ];

    public function subscription()
    {
        return $this->belongsTo(HdSubscription::class, 'subscription_id');
    }

    public function reward()
    {
        return $this->belongsTo(HcSubscriptionReward::class, 'reward_id');
    }
}
