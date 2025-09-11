<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubaccountControllerCopy extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Check if agency is connected //TODO: make helper function for isAgencyConnected
            // if (! $this->isAgencyConnected()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Agency not connected. Please connect your agency first.',
            //         'data'    => [],
            //     ], 400);
            // }

            // Fetch subaccounts from GHL API
            $subaccounts = $this->fetchSubaccountsFromGHL();

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
            // You might want to store this in a settings table or config
            // session(['primary_subaccount' => $locationId]);

            $user = loginUser();
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
            $primarySubaccount = session('primary_subaccount');

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

    private function isAgencyConnected(): bool
    {
        // Check if GHL agency is connected
        // This should check for valid OAuth tokens or connection status
        // For now, return true as placeholder

        // $authuser = auth::user();
        // return @$authuser->token->company_id;

        return true;
    }

    private function fetchSubaccountsFromGHL(): array
    {
        // This should make actual API call to GHL
        // For now, return mock data
        return [
            [
                'id'           => 'loc_123456789',
                'name'         => 'Main Location',
                'address'      => '123 Main St, City, State',
                'phone'        => '+1234567890',
                'email'        => 'main@example.com',
                'website'      => 'https://mainlocation.com',
                'timezone'     => 'America/New_York',
                'country'      => 'US',
                'full_address' => '123 Main St, City, State 12345, US',
            ],
            [
                'id'           => 'loc_987654321',
                'name'         => 'Branch Location',
                'address'      => '456 Branch Ave, City, State',
                'phone'        => '+1987654321',
                'email'        => 'branch@example.com',
                'website'      => 'https://branchlocation.com',
                'timezone'     => 'America/New_York',
                'country'      => 'US',
                'full_address' => '456 Branch Ave, City, State 54321, US',
            ],
            [
                'id'           => 'loc_555666777',
                'name'         => 'Remote Office',
                'address'      => '789 Remote Rd, City, State',
                'phone'        => '+1555666777',
                'email'        => 'remote@example.com',
                'website'      => 'https://remoteoffice.com',
                'timezone'     => 'America/Los_Angeles',
                'country'      => 'US',
                'full_address' => '789 Remote Rd, City, State 98765, US',
            ],
        ];
    }
}
