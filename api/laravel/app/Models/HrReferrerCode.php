<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrReferrerCode extends Model
{
    use HasFactory;
    protected $table = 'hr_referrer_codes';
    protected $fillable = [
        'id',
        'code',
        'player_id',
        'modified_by',
        'created_by'
    ];
}
