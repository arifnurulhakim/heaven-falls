<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPeriodBattlepass extends Model
{
    use HasFactory;

    protected $table = 'hr_period_battlepass';
    public $timestamps = false;


    protected $fillable = [
        'name',
        'start_date',
        'end_date',
    ];

    public function battlepasss()
    {
        return $this->hasMany(HdSubscription::class, 'period_battlepass_id');
    }
}
