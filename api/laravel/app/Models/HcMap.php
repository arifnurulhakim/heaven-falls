<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HcMap extends Model
{
    use HasFactory;

    protected $table = 'hc_maps';
    protected $fillable = [
        'maps_name',
        'win_liberation',
        'lose_liberation',
        'created_by',
        'modified_by'];

    // Relasi dengan HdMissionMap (Missions)
    public function missions()
    {
        return $this->hasMany(HdMissionMap::class, 'maps_id');
    }

    // Relasi dengan HdMissionReward melalui HdMissionMap
    public function rewards()
    {
        return $this->hasManyThrough(
            HdMissionReward::class,  // Model tujuan (rewards)
            HdMissionMap::class,     // Model perantara (missions)
            'maps_id',               // Foreign key di HdMissionMap
            'missions_map_id',       // Foreign key di HdMissionReward
            'id',                    // Local key di HcMap
            'id'                     // Local key di HdMissionMap
        );
    }

    // Relasi dengan User untuk Creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi dengan User untuk Modifier
    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
