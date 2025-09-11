<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubaccountController extends Controller
{
    public function index()
    {
        return view('admin.subaccounts');
    }

    public function subaccountsData(Request $request): JsonResponse
    {
        try {

            // Check if agency is connected //TODO: make helper function for isAgencyConnected
            if (! $this->isAgencyConnected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agency not connected. Please connect your agency first.',
                    'data'    => [],
                ], 400);
            }

            $userSettings = UserSetting::all(); // where('user_id', auth()->id())->get();
            return response()->json([
                'success' => true,
                'message' => 'Subaccounts fetched successfully',
                'data'    => $userSettings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subaccounts: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function updateSubaccount(Request $request): JsonResponse
    {

        try {

            $request->merge([
                'chargeable'      => filter_var($request->chargeable, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'allow_uninstall' => filter_var($request->allow_uninstall, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                // 'paused'          => filter_var($request->paused, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            $request->validate([
                'id'                    => 'required|exists:user_settings,id',
                'chargeable'            => 'required|boolean',
                'allow_uninstall'       => 'required|boolean',
                'amount_charge_percent' => 'required|numeric|min:0|max:100',
                'stripePaymentMothedId' => 'required|string',
                'stripeCustomerId'      => 'required|string',

                // 'paused'                => 'required|boolean',
            ]);

            $userSetting = UserSetting::findOrFail($request->id);
            $userSetting->update([
                'chargeable'               => $request->chargeable,
                'allow_uninstall'          => $request->allow_uninstall,
                'amount_charge_percent'    => $request->amount_charge_percent,
                'stripe_payment_method_id' => $request->stripePaymentMothedId,
                'stripe_customer_id'       => $request->stripeCustomerId,

                // 'paused'                => $request->paused,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subaccount updated successfully',
                'data'    => $userSetting,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update subaccount: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function toggleSubaccount(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id'    => 'required|exists:user_settings,id',
                'field' => 'required|in:chargeable,allow_uninstall',
                'value' => 'required|in:0,1,true,false', // Accept 0, 1, true, false
            ]);

            $userSetting = UserSetting::findOrFail($request->id);
            $value       = filter_var($request->value, FILTER_VALIDATE_BOOLEAN); // Convert to boolean
            $userSetting->update([
                $request->field => $value,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subaccount updated successfully',
                'data'    => $userSetting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subaccount: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    //-------------------------------------------

    public function assignPlans(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'plan'             => 'required|string',
                'subaccount_ids'   => 'required|array',
                'subaccount_ids.*' => 'exists:user_settings,id',
            ]);

            [$price_id, $threshold_amount, $amount_charge_percent, $currency] = explode('|', $request->plan);

            UserSetting::whereIn('id', $request->subaccount_ids)->update([
                'price_id'              => $price_id,
                'threshold_amount'      => $threshold_amount,
                'amount_charge_percent' => $amount_charge_percent,
                'currency'              => $currency,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plans assigned successfully to selected subaccounts.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign plans: ' . $e->getMessage(),
            ], 500);
        }
    }
}
