<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrExpSubscription extends Model
{
    use HasFactory;

    protected $table = 'hr_exp_subscription';
    public $timestamps = false;
    protected $fillable = [
        'player_id',
        'exp',
    ];

    public function player()
    {
        return $this->belongsTo(HdPlayers::class, 'player_id');
    }


}
