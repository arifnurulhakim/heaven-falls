<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrExpBattlepass extends Model
{
    use HasFactory;

    protected $table = 'hr_exp_battlepass';
    public $timestamps = false;
    protected $fillable = [
        'player_id',
        'exp',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

}
