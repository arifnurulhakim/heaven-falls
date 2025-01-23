<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPeriodSubscription extends Model
{
    use HasFactory;

    protected $table = 'hr_period_subscription';
    public $timestamps = false;
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
    ];

    public function subscriptions()
    {
        return $this->hasMany(HdSubscription::class, 'period_battlepass_id');
    }
}
