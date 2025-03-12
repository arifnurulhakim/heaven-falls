<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdGameRecords extends Model
{
    use HasFactory;

    protected $table = 'hd_game_records';

    protected $fillable = [
        'kill',
        'time',
        'map_id',
        'win_or_lose',
        'player_id',
        'created_by',
        'modified_by',
    ];

    public function player()
    {
        return $this->belongsTo(HdPlayer::class, 'player_id');
    }

    public function map()
    {
        return $this->belongsTo(HcMap::class, 'map_id');
    }
}
