<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdBattlepass extends Model
{
    use HasFactory;

    protected $table = 'hd_battlepass';
    public $timestamps = false;

    protected $fillable = [
        'level_battlepass',
        'period_battlepass_id',
        'reach_exp',
    ];

    public function period()
    {
        return $this->belongsTo(HrPeriodBattlepass::class, 'period_battlepass_id');
    }

    public function quests()
    {
        return $this->hasMany(HdBattlepassQuests::class, 'battlepass_id');
    }

    public function rewards()
    {
        return $this->hasMany(HdBattlepassReward::class, 'battlepass_id');
    }
}
