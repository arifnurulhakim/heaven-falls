<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Player extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'hd_players';

    protected $fillable = [
        'username',
        'real_name',
        'email',
        'avatar_url',
        'mobile_number',
        'password',
        'level_r_id',
        'inventory_r_id',
        'country_id',
        'state_id',
        'summary',
        'players_ip_address',
        'players_mac_address',
        'players_os_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function levelPlayer()
    {
        return $this->belongsTo(LevelPlayer::class, 'level_r_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function wallets()
    {
        return $this->hasMany(HdWallet::class, 'player_id');
    }

    public function level()
    {
        return $this->hasOne(HrLevelPlayer::class, 'level_r_id');
    }

    public function inventory()
    {
        return $this->hasOne(HrInventoryPlayer::class, 'id', 'inventory_r_id');
    }

    public function skins()
    {
        return $this->hasMany(HrSkinCharacterPlayer::class, 'players_id');
    }

    public function country()
    {
        return $this->belongsTo(HcCountries::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(HcState::class, 'state_id');
    }





}
