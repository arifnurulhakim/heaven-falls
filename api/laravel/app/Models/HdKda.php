<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HdKda extends Model
{
    use HasFactory;
    protected $table = 'hd_kdas';

    protected $fillable = [
        'player_id',
        'kill',
        'death',
        'assist',
        'room_code',
        'created_by',
        'modified_by'
    ];

    public function player()
    {
        return $this->belongsTo(HdPlayer::class, 'player_id');
    }


}
