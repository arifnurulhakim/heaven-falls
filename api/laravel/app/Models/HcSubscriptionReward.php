<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcSubscriptionReward extends Model
{
    use HasFactory;

    protected $table = 'hc_subscription_rewards';
    public $timestamps = false;
    protected $fillable = [
        'name_item',
        'category',
        'value',
        'created_at',
        'modified_at',
        'created_by',
        'modified_by',
    ];

    public function creator()
    {
        return $this->belongsTo(HdPlayers::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(HdPlayers::class, 'modified_by');
    }
}
