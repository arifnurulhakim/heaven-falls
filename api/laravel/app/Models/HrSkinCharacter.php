<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrSkinCharacter extends Model
{
    use HasFactory;

    protected $table = 'hr_skin_characters';

    protected $fillable = [
        'character_id',
        'name_skin',
        'code_skin',
        'level_reach',
        'image_skin',
        'gender_skin',
        'point_price',
        'created_by',
        'modified_by'
    ];
    public function character()
    {
        return $this->belongsTo(HcCharacter::class, 'character_id', 'id');
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
