<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPlayerSubscription extends Model
{
    use HasFactory;

    protected $table = 'hr_player_subscription';

    protected $fillable = [
        'subscription_id',
        'player_id',
        'status_claimed',
    ];

    public function subscription()
    {
        return $this->belongsTo(HdSubscription::class, 'subscription_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
