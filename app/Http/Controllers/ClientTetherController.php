<?php
namespace App\Http\Controllers;

use App\Models\ClientTetherCredentials;
// use App\Models\IntegrationLog;
use App\Repositories\ClientTetherRepository;
use App\Repositories\GhlRepository;
use App\Services\ClientTetherIntegration;
use Illuminate\Http\Request;

class ClientTetherController extends Controller
{
    protected $clientTetherIntegration;
    protected $clientTetherRepository;
    protected $ghlRepository;

    public function __construct(
        ClientTetherIntegration $clientTetherIntegration,
        ClientTetherRepository $clientTetherRepository,
        GhlRepository $ghlRepository
    ) {
        $this->clientTetherIntegration = $clientTetherIntegration;
        $this->clientTetherRepository  = $clientTetherRepository;
        $this->ghlRepository           = $ghlRepository;
    }

    public function credentials()
    {
        $user       = loginUser();
        $locationId = $user->location_id;

        $credentials = ClientTetherCredentials::where('location_id', $locationId)->first() ?? new ClientTetherCredentials();
        return view('location.client_tether_credentials', compact('locationId', 'credentials'));
    }

    public function saveCredentials(Request $request)
    {
        $user       = loginUser();
        $locationId = $user->location_id;

        $request->validate([
            'x_access_token' => 'required|string',
            'x_web_token'    => 'required|string',
        ]);

        ClientTetherCredentials::updateOrCreate(
            ['location_id' => $locationId],
            [
                'x_access_token' => $request->x_access_token,
                'x_web_token'    => $request->x_web_token,
            ]
        );

        return response()->json(['message' => 'Credentials saved successfully']);
    }

    public function getClientTetherUsers(string $locationId)
    {
        $users = $this->clientTetherRepository->getUserList($locationId);

        return response()->json(['users' => array_map(function ($user) {

            $name = $user['user_first_name'] . ' ' . $user['user_last_name'] . ' (' . $user['user_email'] . ')';

            return ['id' => $user['user_id'], 'name' => $name];
        }, $users['data'])]);
    }

    public function getCrmUsers(string $locationId)
    {
        $users = $this->ghlRepository->getUserList($locationId);

        return response()->json(['users' => array_map(function ($user) {
            return ['id' => $user->id, 'name' => $user->name . ' (' . $user->email . ')'];
        }, $users)]);
    }

    //=============================== for logs may be remove this code =========================

    // /**
    //  * Display the logs page.
    //  *
    //  * @param string $locationId
    //  * @return \Illuminate\View\View
    //  */
    // public function showLogs(string $locationId)
    // {
    //     return view('location.client_tether_logs', compact('locationId'));
    // }

    // /**
    //  * Handle DataTables AJAX request for logs.
    //  *
    //  * @param string $locationId
    //  * @return \Yajra\DataTables\DataTableAbstract
    //  */
    // public function logsData(string $locationId)
    // {
    //     $query = IntegrationLog::query()
    //         ->select(['id', 'type', 'message', 'data', 'location_id', 'created_at'])
    //         ->where('location_id', $locationId)->latest('created_at');

    //     // if ($request->filled('type')) {
    //     //     $logs->where('type', $request->input('type'));
    //     // }

    //     // $keyword = $request->input('q', '');

    //     return DataTables::of($query)
    //         // ->filterColumn('type', function ($query, $keyword) {
    //         //     $query->where('type', 'like', "%{$keyword}%");
    //         // })
    //         ->make(true);
    // }

    // /**
    //  * Get unique log types for filter dropdown.
    //  *
    //  * @param string $locationId
    //  * @param Request $request
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // public function getLogTypes(string $locationId, Request $request)
    // {
    //     $search = $request->input('q', '');

    //     $types = IntegrationLog::query()
    //         ->where('location_id', $locationId)
    //         ->when($search, function ($query, $search) {
    //             return $query->where('type', 'like', "%{$search}%");
    //         })
    //         ->distinct()
    //         ->pluck('type')
    //         ->take(10)
    //         ->values();

    //     return response()->json(['types' => $types]);
    // }
    //============================================================================

}
