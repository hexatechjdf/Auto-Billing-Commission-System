<?php
namespace App\Models;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    // use HasFactory;

    protected $fillable = [
        "user_id",
        "location_id",
        'location_name',

        "email",
        'contact_id',
        'contact_name',
        'contact_phone',

        "stripe_payment_method_id",
        "stripe_customer_id",
        "chargeable",
        "allow_uninstall",
        "threshold_amount",
        "currency",
        "amount_charge_percent",
        "price_id",
        "last_checked_at",
        "pause_at",
        "paused",
    ];

    // protected $guarded = [];

    protected $attributes = [
        'amount_charge_percent' => 2.0,
        'price_id'              => null,
        'chargeable'            => true,
        'allow_uninstall'       => false,
        'paused'                => false,
    ];

    protected $casts = [
        'chargeable'            => 'boolean',
        'allow_uninstall'       => 'boolean',
        'paused'                => 'boolean',
        'threshold_amount'      => 'decimal:2',
        'amount_charge_percent' => 'decimal:2',
        'last_checked_at'       => 'datetime',
        'pause_at'              => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'location_id', 'location_id');
    }
}
