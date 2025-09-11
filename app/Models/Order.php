<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        // 'price_id',
        'location_id',
        'contact_id',
        // 'live_mode',
        'user_id',
        'amount',
        'currency',
        'amount_charge_percent',
        'calculated_commission_amount',
        'transaction_id',
        'status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
}
