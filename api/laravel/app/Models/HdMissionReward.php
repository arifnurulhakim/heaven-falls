<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdMissionReward extends Model
{
    use HasFactory;

    protected $table = 'hd_missions_rewards';
    protected $fillable = [
        'missions_map_id', 'id_player', 'reward_currency',
        'reward_exp', 'claim_status', 'created_by', 'modified_by'
    ];

    public function mission()
    {
        return $this->belongsTo(HdMissionMap::class, 'missions_map_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'id_player'); // Replace with actual player model
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
