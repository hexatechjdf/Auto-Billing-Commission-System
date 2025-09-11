<?php
namespace App\Http\Controllers\Admin;

use App\Helper\CRM;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $settings   = Setting::pluck('value', 'key')->toArray();
        $connecturl = CRM::directConnect();
        $scopes     = CRM::$scopes;
        $authuser   = auth::user();

        $company_name = null;
        $company_id   = null;

        if (@$authuser->token->company_id) {
            // dd(CRM::getCompany($authuser));

            list($company_name, $company_id) = CRM::getCompany($authuser);
        }

        return view('admin.settings', get_defined_vars());

        // return view('admin.setting.index', get_defined_vars());
    }

    public function save(Request $request)
    {
        $user    = loginUser();
        $authKey = 'oauth';

        foreach ($request->setting ?? [] as $key => $value) {
            if (in_array($key, [$authKey])) {
                continue;
            }
            save_settings($key, $value);
        }

        return response()->json(['success' => true, 'message' => 'Data saved successfully']);
    }

    // public function locations(Request $request)
    // {
    //     if ($request->ajax()) {
    //         try {
    //             list($status, $message, $detail, $load_more) = CRM::fetchLocations($request);

    //             return response()->json(['status' => $status, 'message' => $message, 'detail' => $detail, 'load' => $load_more]);
    //         } catch (\Throwable $th) {
    //             return response()->json(['status' => false, 'message' => 'Unknown Error']);
    //         }
    //     }
    //     return view('admin.setting.locations');
    // }

    // public function locationsSet(Request $request)
    // {
    //     try {
    //         list($status, $message) = CRM::authChecking($request);

    //         return response()->json(['status' => $status, 'message' => $message]);
    //     } catch (\Throwable $th) {

    //     }
    //     return response()->json(['status' => false, 'message' => 'Unknown Error']);
    // }

    public function subAccounts(): JsonResponse
    {
        try {
            // Check if agency is connected
            if (! isAgencyConnected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agency not connected. Please connect your agency first.',
                    'data'    => [],
                ], 400);
            }

            // Fetch subaccounts from GHL API
            // $subaccounts = $this->fetchSubaccountsFromGHL();

            $request                                          = new Request();
            list($status, $message, $subaccounts, $load_more) = CRM::fetchLocations($request);

            return response()->json([
                'success' => true,
                'message' => 'Subaccounts fetched successfully',
                'data'    => $subaccounts,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subaccounts: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function setPrimary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'location_id' => 'required|string',
            ]);

            $locationId = $request->input('location_id');

            // Store the primary subaccount selection
            save_settings('primary_subaccount', $locationId);

            return response()->json([
                'success' => true,
                'message' => 'Primary subaccount set successfully',
                'data'    => ['location_id' => $locationId],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary subaccount: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function getPrimary(): JsonResponse
    {
        try {
            $primarySubaccount = supersetting($key = 'primary_subaccount');

            return response()->json([
                'success' => true,
                'message' => 'Primary subaccount retrieved',
                'data'    => ['location_id' => $primarySubaccount],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get primary subaccount: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function userProfile(Request $request)
    {
        $user = loginUser();

        $request->validate([
            'username' => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);

        try {

            $username = $request->username;
            $email    = $request->email;
            $password = $request->password;

            // $userId = $user->id;

            // $userExist = User::where('email', $email)
            //     ->where('id', '<>', $userId)
            //     ->first();

            // if ($userExist) {
            //     if (!empty($password)) {
            //         $user->password = bcrypt($password);
            //         $user->save();
            //         return response()->json(['status' => 'Success', 'message' => 'Password updated successfully']);
            //     } else {
            //         return response()->json(['status' => 'Error', 'message' => 'Password is required'], 400);
            //     }
            // } else {
            $user->email = $email;
            if (! empty($password)) {
                $user->password = bcrypt($password);
            }
            $user->name = $username;
            $user->save();
            return response()->json(['status' => 'Success', 'message' => 'User profile updated successfully']);
            // }
        } catch (\Exception $e) {
            return response()->json(['status' => 'Error', 'message' => $e->getMessage()], 500);
        }
    }

    // private function fetchSubaccountsFromGHL(): array
    // {
    //     // This should make actual API call to GHL
    //     // For now, return mock data
    //     return [
    //         [
    //             'id'           => 'loc_123456789',
    //             'name'         => 'Main Location',
    //             'address'      => '123 Main St, City, State',
    //             'phone'        => '+1234567890',
    //             'email'        => 'main@example.com',
    //             'website'      => 'https://mainlocation.com',
    //             'timezone'     => 'America/New_York',
    //             'country'      => 'US',
    //             'full_address' => '123 Main St, City, State 12345, US',
    //         ],
    //         [
    //             'id'           => 'loc_987654321',
    //             'name'         => 'Branch Location',
    //             'address'      => '456 Branch Ave, City, State',
    //             'phone'        => '+1987654321',
    //             'email'        => 'branch@example.com',
    //             'website'      => 'https://branchlocation.com',
    //             'timezone'     => 'America/New_York',
    //             'country'      => 'US',
    //             'full_address' => '456 Branch Ave, City, State 54321, US',
    //         ],
    //         [
    //             'id'           => 'loc_555666777',
    //             'name'         => 'Remote Office',
    //             'address'      => '789 Remote Rd, City, State',
    //             'phone'        => '+1555666777',
    //             'email'        => 'remote@example.com',
    //             'website'      => 'https://remoteoffice.com',
    //             'timezone'     => 'America/Los_Angeles',
    //             'country'      => 'US',
    //             'full_address' => '789 Remote Rd, City, State 98765, US',
    //         ],
    //     ];
    // }
}
