<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcLevel extends Model
{
    use HasFactory;

    protected $table = 'hc_levels';

    protected $fillable = [
        'name',
        'desc',
        'hud',
        'level_reach',
        'created_by',
        'modified_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
