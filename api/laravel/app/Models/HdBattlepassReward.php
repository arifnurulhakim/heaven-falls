<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdBattlepassReward extends Model
{
    use HasFactory;

    protected $table = 'hd_battlepass_rewards';
    public $timestamps = false;
    protected $fillable = [
        'battlepass_id',
        'reward_id',

    ];

    public function battlepass()
    {
        return $this->belongsTo(HdBattlepass::class, 'battlepass_id');
    }

    public function reward()
    {
        return $this->belongsTo(HcBattlepassReward::class, 'reward_id');
    }
}
