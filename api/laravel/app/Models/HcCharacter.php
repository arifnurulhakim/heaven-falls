<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcCharacter extends Model
{
    use HasFactory;

    protected $table = 'hc_characters';

    protected $fillable = [
        'name',
        'desc',
        'assets_name',
        'gender_character',
        'point_price',
        'character_role_id',
        'created_by',
        'modified_by'
    ];

    public function role()
    {
        return $this->belongsTo(HcCharacterRole::class, 'character_role_id');
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
