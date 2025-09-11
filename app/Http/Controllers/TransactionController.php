<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class TransactionController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $isAdmin = $user->role == 1;

        //isAdmin = false;

        $locationId = $isAdmin ? null : $user->location_id;
        $locations  = $isAdmin ? UserSetting::select('location_id', 'email')->orderBy('email', 'asc')->get() : [];

        return view('admin.transactions', compact('isAdmin', 'locationId', 'locations'));
    }

    public function data(Request $request)
    {
        $user = auth()->user();

        $isAdmin = $user->role == 1;

        $query = Transaction::query();

        if ($isAdmin) {

            if (isset($request->location_id)) {
                $query->where('location_id', $request->location_id);
            }
        } else {

            if (! $user->location_id) {
                return response()->json([
                    'message' => 'Location ID is required',
                    'data'    => [],
                ], 400);
            }
            $query->where('location_id', $user->location_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        if (isset($request->status)) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($transaction) {
                return '<button class="btn btn-sm btn-outline-primary view-orders" data-id="' . $transaction->id . '"><i class="fas fa-eye"></i> View Orders</button>';
            })
            ->addColumn('status_text', function ($transaction) {
                $statuses = ['Pending', 'Paid', 'Failed'];
                $colors   = ['warning', 'success', 'danger'];
                return '<span class="badge bg-' . $colors[$transaction->status] . '">' . $statuses[$transaction->status] . '</span>';
            })
            ->addColumn('sum_commission_amount_formatted', function ($transaction) {
                return $transaction->currency . ' ' . number_format($transaction->sum_commission_amount, 2);
            })
            ->rawColumns(['action', 'status_text'])
            ->make(true);
    }

    public function ordersData(Request $request)
    {
        //TOOD: some validation
        $query = Order::where('transaction_id', $request->transaction_id);

        return DataTables::of($query)
            ->addColumn('amount_formatted', function ($order) {
                return $order->currency . ' ' . number_format($order->amount, 2);
            })
            ->addColumn('calculated_commission_amount_formatted', function ($order) {
                return $order->currency . ' ' . number_format($order->calculated_commission_amount, 2);
            })
            ->addColumn('action', function ($order) {
                return '<button class="btn btn-sm btn-outline-info view-order-details"
                data-id="' . $order->id . '"
                data-order-record=\'' . e(json_encode($order)) . '\'
            >
                <i class="fas fa-eye"></i> Details
            </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // public function orderDetails(Request $request)
    // {
    //     $order = Order::findOrFail($request->order_id);
    //     return response()->json(['data' => $order]);
    // }
}
