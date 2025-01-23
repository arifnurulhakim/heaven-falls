<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcBattlepassReward extends Model
{
    use HasFactory;

    protected $table = 'hc_battlepass_rewards';
    public $timestamps = false;
    protected $fillable = [
        'name_item',
        'category',
        'skin_id',
        'type',
        'value',
        'created_at',
        'modified_at',
        'created_by',
        'modified_by',
    ];

    public function creator()
    {
        return $this->belongsTo(Player::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(Player::class, 'modified_by');
    }
    public function skin()
    {
        return $this->belongsTo(HrSkinCharacters::class, 'skin_id');
    }
}

