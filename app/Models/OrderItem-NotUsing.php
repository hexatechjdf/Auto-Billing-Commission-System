<?php
namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    // use HasFactory;

    protected $fillable = [
        'order_id',
        'location_id',
        'item_name',
        'qty',
        'product_id',
        'product_name',
        'price_id',
        'price_name',
        'amount',
        'currency',
        'type',
        'batch_id',
        'metadata',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
