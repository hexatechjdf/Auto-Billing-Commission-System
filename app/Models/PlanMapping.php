<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        "location_id",
        "product_id",
        "product_name",
        "price_id",
        "price_name",
        "threshold_amount",
        "amount_charge_percent",
    ];

    protected $attributes = [
        'amount_charge_percent' => 2,
        'currency'              => 'USD',
    ];

}
