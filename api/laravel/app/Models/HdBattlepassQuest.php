<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdBattlepassQuest extends Model
{
    use HasFactory;

    protected $table = 'hd_battlepass_quests';
    public $timestamps = false;
    protected $fillable = [
        'period_battlepass_id',
        'quest_id',
    ];

    public function period()
    {
        return $this->belongsTo(HrPeriodBattlepass::class, 'period_battlepass_id');
    }

    public function quest()
    {
        return $this->belongsTo(HcQuestBattlepass::class, 'quest_id');
    }
}
