<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcCurrency extends Model
{
    use HasFactory;

    protected $table = 'hc_currencies';

    protected $fillable = [
        'name',
        'code',
        'value',
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
