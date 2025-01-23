<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcCharacterRole extends Model
{
    use HasFactory;

    protected $table = 'hc_character_roles';

    protected $fillable = [
        'role',
        'hitpoints',
        'damage',
        'defense',
        'speed',
        'skills',
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
