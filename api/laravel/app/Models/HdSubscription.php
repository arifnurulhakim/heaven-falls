<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdSubscription extends Model
{
    use HasFactory;

    protected $table = 'hd_subscription';
    public $timestamps = false;
    protected $fillable = [
        'level_subscription',
        'period_subscription_id',
        'reach_exp',
    ];

    public function period()
    {
        return $this->belongsTo(HrPeriodSubscription::class, 'period_subscription_id');
    }
    public function rewards()
    {
        return $this->hasMany(HdSubscriptionReward::class, 'subscription_id');
    }
}
