<?php
namespace App\Http\Controllers;

use App\Helper\CRM;
use App\Jobs\SyncGhlSubaccountJob;
use App\Models\UserSetting;
use App\Services\PlanMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class SubaccountController extends Controller
{
    // protected $planMappingService;

    public function __construct(protected PlanMappingService $planMappingService)
    {
        // $this->planMappingService = $planMappingService;
    }

    public function index()
    {
        $isAgencyConnected = isAgencyConnected(); // Assuming this helper exists
        return view('admin.subaccounts', compact('isAgencyConnected'));
    }

    public function subaccountsData(Request $request): JsonResponse
    {
        try {
            // $query = UserSetting::select([
            //     'id',
            //     'location_id',
            //     'email',
            //     'chargeable',
            //     'allow_uninstall',
            //     'stripe_payment_method_id',
            //     'stripe_customer_id',
            //     'amount_charge_percent',
            //     'paused',
            //     'pause_at',
            //     'price_id',
            //     'threshold_amount',
            //     'currency',
            //     'last_checked_at',
            //     'created_at',
            // ]);

            $query = UserSetting::query();

            return DataTables::of($query)
                ->addColumn('status', function ($subaccount) {
                    return '<span class="badge bg-' . ($subaccount->paused ? 'warning' : 'success') . '">' . ($subaccount->paused ? 'Paused' : 'Active') . '</span>';
                })
                ->addColumn('charge_percent', function ($subaccount) {
                    return '<span class="badge bg-info">' . number_format($subaccount->amount_charge_percent, 2) . '%</span>';
                })
                ->addColumn('action', function ($subaccount) {
                    return '<button class="btn btn-sm btn-outline-primary" onclick="editSubaccount(' . $subaccount->id . ')"><i class="fas fa-edit"></i></button>';
                })
                ->filterColumn('location_id', function ($query, $keyword) {
                    $query->where('location_id', 'like', "%{$keyword}%");
                })
                ->filterColumn('email', function ($query, $keyword) {
                    $query->where('email', 'like', "%{$keyword}%");
                })
                ->rawColumns(['status', 'charge_percent', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subaccounts: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function syncSubaccounts(Request $request): JsonResponse
    {
        try {
            if (! isAgencyConnected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agency not connected. Please connect your agency first.',
                ], 400);
            }

            $page      = 1;
            $perPage   = 100;
            $locations = [];
            $hasMore   = true;

            while ($hasMore) {

                $request = new Request(['page' => $page, 'limit' => $perPage]);

                list($status, $message, $subaccounts, $hasMore) = CRM::fetchLocations($request);

                // dd($status, $message, $subaccounts, $hasMore);

                if (! $status) { //! isset($subaccounts)
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to fetch locations from CRM.',
                    ], 500);
                }

                Log::info('response Subaccount ', ['subaccounts' => $subaccounts]);

                foreach ($subaccounts as $subaccount) {
                    if ($subaccount->already_exist) {
                        //Log::info('Subaccount already exists', ['location_id' => $subaccount->id]);
                        continue;
                    }
                    SyncGhlSubaccountJob::dispatch($subaccount);
                }

                $locations = array_merge($locations, $subaccounts);

                $page++;

                if ($hasMore) {
                    sleep(1); // 1-second delay between requests
                }
            }

            Log::info('all Subaccount fetched ', ['locations' => $locations]);

            return response()->json([
                'success' => true,
                'message' => 'Subaccount sync jobs dispatched successfully.',
                'data'    => ['count' => count($subaccounts)],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync subaccounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPlanMappings(Request $request): JsonResponse
    {
        try {
            $primarySubaccountId = supersetting($key = 'primary_subaccount');

            if (! $primarySubaccountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Primary subaccount not found.',
                    'data'    => [],
                ], 400);
            }

            // $planMappings = PlanMapping::where('location_id', $primarySubaccountId)->get();
            $planMappings = $this->planMappingService->getPlanMappingsByLocationId($primarySubaccountId);
            // dd($planMappings);
            if ($planMappings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No plan mappings found for the primary subaccount.',
                    'data'    => [],
                ], 404);
            }

            $plans = $planMappings->map(function ($plan) {
                return [
                    'value'   => implode('|', [
                        $plan->price_id,
                        $plan->threshold_amount,
                        $plan->amount_charge_percent,
                        $plan->currency,
                    ]),
                    'display' => "{$plan->product_name} | {$plan->threshold_amount} | {$plan->amount_charge_percent}%",
                ];
            });

            $subaccounts = UserSetting::select(['id', 'location_id', 'location_name', 'email'])->get()->chunk(100);

            return response()->json([
                'success' => true,
                'message' => 'Plan mappings and subaccounts fetched successfully.',
                'data'    => [
                    'plans'       => $plans,
                    'subaccounts' => $subaccounts,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plan mappings: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

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

    public function updateSubaccount(Request $request): JsonResponse
    {
        try {
            $request->merge([
                'chargeable'      => filter_var($request->chargeable, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'allow_uninstall' => filter_var($request->allow_uninstall, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'paused'          => filter_var($request->paused, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            $request->validate([
                'id'                       => 'required|exists:user_settings,id',
                'chargeable'               => 'required|boolean',
                'allow_uninstall'          => 'required|boolean',
                'amount_charge_percent'    => 'required|numeric|min:0|max:100',
                'threshold_amount'         => 'required|numeric|min:0',
                'currency'                 => 'required|string|size:3',
                'paused'                   => 'required|boolean',
                'stripe_payment_method_id' => 'required|string',
                'stripe_customer_id'       => 'required|string',
                'contact_id'               => 'required|string',
                'contact_phone'            => 'required|string',
                'location_name'            => 'required|string',

            ]);

            $userSetting = UserSetting::findOrFail($request->id);
            $userSetting->update([
                'chargeable'               => $request->chargeable,
                'allow_uninstall'          => $request->allow_uninstall,
                'amount_charge_percent'    => $request->amount_charge_percent,
                'threshold_amount'         => $request->threshold_amount,
                'currency'                 => $request->currency,
                'paused'                   => $request->paused,
                'contact_id'               => $request->contact_id,
                'contact_phone'            => $request->contact_phone
                ,
                'location_name'            => $request->location_name,

                'stripe_payment_method_id' => $request->stripe_payment_method_id,
                'stripe_customer_id'       => $request->stripe_customer_id,
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
                'field' => 'required|in:chargeable,allow_uninstall,paused',
                'value' => 'required|in:0,1,true,false',
            ]);

            $userSetting = UserSetting::findOrFail($request->id);
            $value       = filter_var($request->value, FILTER_VALIDATE_BOOLEAN);
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
}
