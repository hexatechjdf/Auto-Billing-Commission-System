<?php
namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Services\BatchService;
use App\Services\OrderService;
use App\Services\TransactionService;
use App\Services\UserSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $orderService;
    protected $transactionService;
    protected $batchService;
    protected $userSettingService;

    public function __construct(
        OrderService $orderService,
        TransactionService $transactionService,
        BatchService $batchService,
        UserSettingService $userSettingService
    ) {
        $this->orderService       = $orderService;
        $this->transactionService = $transactionService;
        $this->batchService       = $batchService;
        $this->userSettingService = $userSettingService;
    }

    public function dashboard()
    {
        // Check if agency is connected (you'll need to implement this logic)
        $agencyConnected = $this->checkAgencyConnection();

        return view('admin.dashboard', compact('agencyConnected'));
    }

    public function subaccounts()
    {
        return view('admin.subaccounts');
    }

    public function subaccountsData(Request $request): JsonResponse
    {
        try {
            $userSettings = UserSetting::where('user_id', auth()->id())->get();
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
            $request->validate([
                'id'                    => 'required|exists:user_settings,id',
                'chargeable'            => 'required|boolean',
                'allow_uninstall'       => 'required|boolean',
                'amount_charge_percent' => 'required|numeric|min:0|max:100',
                'paused'                => 'required|boolean',
            ]);

            $userSetting = UserSetting::findOrFail($request->id);
            $userSetting->update([
                'chargeable'            => $request->chargeable,
                'allow_uninstall'       => $request->allow_uninstall,
                'amount_charge_percent' => $request->amount_charge_percent,
                'paused'                => $request->paused,
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

    // public function planMappings()
    // {
    //     return view('admin.plan-mappings');
    // }

    public function transactions()
    {
        $transactions = $this->transactionService->getTransactionsForPeriod(
            now()->subDays(30),
            now()
        );

        return view('admin.transactions', compact('transactions'));
    }

    public function orders()
    {
        $orders = $this->orderService->getOrdersByStatus('succeeded');

        return view('admin.orders', compact('orders'));
    }

    public function settings()
    {
        return view('admin.settings');
    }

    private function checkAgencyConnection()
    {
        // This should check if GHL agency is connected
        // For now, return false as placeholder
        return false;
    }
}
