<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcQuestBattlepass extends Model
{
    use HasFactory;

    protected $table = 'hc_quest_battlepass';
    public $timestamps = false;
    protected $fillable = [
        'name_quest',
        'quest_code',
        'description_quest',
        'reward_exp',
        'category',
        'target',
    ];

    public function progress()
    {
        return $this->hasMany(HrProgressBattlepass::class, 'quest_battlepass_id');
    }

    public function experiences()
    {
        return $this->hasMany(HrExpBattlepass::class, 'quest_battlepass_id');
    }
}
