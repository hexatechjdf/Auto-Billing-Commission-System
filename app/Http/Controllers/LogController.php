<?php
namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LogController extends Controller
{
    public function index()
    {
        return view('location.logs.index');
    }

    public function data(Request $request)
    {
        $user       = loginUser();
        $locationId = $user->location_id;

        $logs = Log::where('location_id', $locationId)->latest('created_at'); // Fetch all records ordered by latest

        // Optional filter by type
        if ($request->filled('type')) {
            $logs->where('type', $request->input('type'));
        }

        return DataTables::of($logs)
        // ->addColumn('type_name', fn($log) => $log->type == 1 ? 'CRM' : 'ServiceTitan')
            ->addColumn('type_name', fn($log) => $log->type == Log::TYPE_CRM_TO_CT ? 'CRM' : ($log->type == Log::TYPE_CT_TO_CRM ? 'ClientTether' : 'Warning'))
            ->addColumn('action', function ($log) {
                $actions = '<button class="btn btn-sm btn-info detail-btn" data-log-id="' . $log->id . '"><i class="fas fa-eye"></i> Detail</button>';
                if ($log->status == 3 && $log->type == 1) {
                    $actions .= ' <button class="btn btn-sm btn-primary retry-btn" data-log-id="' . $log->id . '" data-type="' . $log->type . '" data-payload=\'' . json_encode($log->payload) . '\'><i class="fa-solid fa-rotate-right"></i> Retry</button>';
                }
                return $actions;
            })
            ->editColumn('status', function ($log) {
                $statusLabels = [
                    0 => ['class' => 'bg-secondary text-white', 'text' => 'Queued'],
                    1 => ['class' => 'bg-warning text-dark', 'text' => 'Processing'],
                    2 => ['class' => 'bg-success', 'text' => 'Succeeded'],
                    3 => ['class' => 'bg-danger', 'text' => 'Failed'],
                ];

                $status = $statusLabels[$log->status] ?? ['class' => 'bg-secondary', 'text' => 'Unknown'];

                return '<span class="badge ' . $status['class'] . '">' . $status['text'] . '</span>';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function show($id)
    {
        $log = Log::findOrFail($id);
        return response()->json($log);
    }
}
