<?php
namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\TransactionService;

class AdminController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected TransactionService $transactionService,
    ) {
    }

    public function dashboard()
    {
        abort(403, 'Unauthorized action');

        // Check if agency is connected (you'll need to implement this logic)
        $agencyConnected = isAgencyConnected();

        return view('admin.dashboard', compact('agencyConnected'));
    }

    // public function subaccounts()
    // {
    //     return view('admin.subaccounts');
    // }

    // public function planMappings()
    // {
    //     return view('admin.plan-mappings');
    // }

    // public function transactions()
    // {
    //     $transactions = $this->transactionService->getTransactionsForPeriod(
    //         now()->subDays(30),
    //         now()
    //     );

    //     return view('admin.transactions', compact('transactions'));
    // }

    // public function orders()
    // {
    //     $orders = $this->orderService->getOrdersByStatus('succeeded');

    //     return view('admin.orders', compact('orders'));
    // }

    // public function settings()
    // {
    //     return view('admin.settings');
    // }
}
