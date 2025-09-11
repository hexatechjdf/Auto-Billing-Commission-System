<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return view('admin.orders');
    }

    public function data(Request $request): JsonResponse
    {
        $query = Order::query();

        // Location filter
        if ($request->location_id) {
            $query->where('location_id', $request->location_id);
        }

        // Date filter
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->filter) {
            switch ($request->filter) {
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                case 'this_year':
                    $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                    break;
            }
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched successfully',
            'data'    => $orders,
        ]);
    }

    public function details($id): JsonResponse
    {
                                         // $order = Order::with('items')->findOrFail($id);
        $order = Order::findOrFail($id); //TODO:  on frontend i think need to change to display data from payload (maybe get just payload insted of whole row)
        return response()->json([
            'success' => true,
            'message' => 'Order details fetched successfully',
            'data'    => $order,
        ]);
    }
}
