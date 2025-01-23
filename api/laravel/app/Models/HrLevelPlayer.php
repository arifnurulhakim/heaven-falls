<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrLevelPlayer extends Model
{
    use HasFactory;

    protected $table = 'hr_level_players';

    protected $fillable = [
        'level_id',
        'exp',
        'created_by',
        'modified_by'
    ];



    public function level()
    {
        return $this->belongsTo(HcLevel::class, 'level_id');
    }
}
