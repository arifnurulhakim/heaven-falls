<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcStatCharacter extends Model
{
    use HasFactory;

    protected $table = 'hc_stat_characters';

    protected $fillable = [
        'character_id',
        'level',
        'hitpoints',
        'damage',
        'defense',
        'speed',
        'skills',
        'created_by',
        'modified_by',
    ];

    public function character()
    {
        return $this->belongsTo(HcCharacter::class, 'character_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
