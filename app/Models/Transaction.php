<?php
namespace App\Models;

use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    const STATUS_PENDING = 0;
    const STATUS_PAID    = 1;
    const STATUS_FAILED  = 2;

    protected $fillable = [
        'location_id',
        'sum_commission_amount',
        'currency',
        'status',
        'metadata',
        'charged_at',
        'reason',
        'pm_intent',
        'invoice_id',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'charged_at' => 'datetime',
    ];

    // protected $dates = [
    //     'charged_at',
    // ];

    public function userSetting()
    {
        return $this->belongsTo(UserSetting::class, 'location_id', 'location_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'transaction_id');
    }
}
