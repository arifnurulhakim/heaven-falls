<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdMissionMap extends Model
{
    use HasFactory;

    protected $table = 'hd_missions_map';
    protected $fillable = [
        'maps_id',
        'missions_name',
        'condition',
        'backstory',
        'type_missions',
        'target_missions',
        'reward_currency',
        'reward_exp',
        'status_missions',
        'dificulity',
        'created_by',
        'modified_by'
    ];

    public function map()
    {
        return $this->belongsTo(HcMap::class, 'maps_id');
    }
    public function rewards()
    {
        return $this->hasMany(HdMissionReward::class, 'missions_map_id');
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
