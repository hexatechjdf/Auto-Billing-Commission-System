<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmToken extends Model
{
    use HasFactory;
    public $table      = 'crm_tokens';
    protected $guarded = [];

    public function urefresh(): bool
    {
        $is_refresh = false;
        try {
            list($is_refresh, $token) = \CRM::getRefreshToken($this->user_id, $this, true);
            //\Log::info("Token refreshed successfully. New token:". $token);
        } catch (\Exception $e) {
            return 500;
        }
        return $is_refresh;
    }

    // public function scopeCompanyWithoutLocation($query, $companyId)
    // {
    //     // return $query->where('company_id', $companyId)
    //     //     ->where(function ($q) {
    //     //         $q->whereNull('location_id')
    //     //             ->orWhere('location_id', '');
    //     //     });

    //     return $query->where(['company_id' => $companyId, 'user_type' => \CRM::$lang_com])
    //         ->where(function ($q) {
    //             $q->whereNull('location_id')
    //                 ->orWhere('location_id', '');
    //         });

    // }
}
