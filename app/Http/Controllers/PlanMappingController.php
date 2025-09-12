<?php
namespace App\Http\Controllers;

use App\Services\PlanMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanMappingController extends Controller
{
    protected $planMappingService;

    public function __construct(PlanMappingService $planMappingService)
    {
        $this->planMappingService = $planMappingService;
    }

    public function index()
    {
        return view('admin.plan-mappings');
    }

    public function planMappings(Request $request): JsonResponse
    {
        try {
            $locationId = $request->query('location_id');

            if (! $locationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location ID is required',
                    'data'    => [],
                ], 400);
            }

            $planMappings = $this->planMappingService->getPlanMappingsByLocationId($locationId);

            return response()->json([
                'success' => true,
                'message' => 'Plan mappings fetched successfully',
                'data'    => $planMappings,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plan mappings: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    // public function fetchProducts(Request $request): JsonResponse
    // {
    //     try {
    //         $locationId = $request->query('location_id');

    //         if (! $locationId) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Location ID is required',
    //                 'data'    => [],
    //             ], 400);
    //         }

    //         // Fetch products from GHL API
    //         $products = $this->fetchProductsFromGHL($locationId);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Products fetched successfully',
    //             'data'    => $products,
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch products: ' . $e->getMessage(),
    //             'data'    => [],
    //         ], 500);
    //     }
    // }

    public function syncPrices(Request $request): JsonResponse
    {
        try {
            $locationId = $request->input('location_id');

            if (! $locationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location ID is required',
                    'data'    => [],
                ], 400);
            }

            // Fetch latest products and prices from GHL
            // $products = $this->fetchProductsFromGHL($locationId);

            $response = $this->planMappingService->fetchInventories($locationId);

            if (! isset($response['status']) || $response['status'] === false) {
                throw new \Exception($response['message'] ?? 'Failed to fetch inventories');
            }

            $inventories = $response['inventories'];

            // Sync plan mappings
            $mappings = $this->planMappingService->syncPlanMappingsForLocation($locationId, $inventories);

            return response()->json([
                'success' => true,
                'message' => 'Prices synced successfully',
                'data'    => $mappings,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync prices: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function updateMapping(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'threshold_amount'      => 'sometimes|numeric|min:0',
                'amount_charge_percent' => 'sometimes|numeric|min:0|max:100',
            ]);

            $data = $request->only(['threshold_amount', 'amount_charge_percent']);

            $mapping = $this->planMappingService->updatePlanMapping($id, $data);

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan mapping not found',
                    'data'    => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Plan mapping updated successfully',
                'data'    => $mapping,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan mapping: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    // private function fetchProductsFromGHL($locationId): array
    // {
    //     // This should make actual API call to GHL to fetch products and their prices
    //     // For now, return mock data
    //     // return [];
    // }
}
