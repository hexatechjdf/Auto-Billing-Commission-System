<?php
namespace App\Services;

use App\Repositories\PlanMappingRepository;

class PlanMappingService
{
    protected $planMappingRepository;

    public function __construct(PlanMappingRepository $planMappingRepository)
    {
        $this->planMappingRepository = $planMappingRepository;
    }

    public function createPlanMapping(array $data)
    {
        return $this->planMappingRepository->create($data);
    }

    public function getPlanMappingById($id)
    {
        return $this->planMappingRepository->findById($id);
    }

    public function getPlanMappingsByLocationId($locationId)
    {
        return $this->planMappingRepository->findByLocationId($locationId);
    }

    public function getPlanMappingByPriceId($priceId)
    {
        return $this->planMappingRepository->findByPriceId($priceId);
    }

    public function updatePlanMapping($id, array $data)
    {
        return $this->planMappingRepository->update($id, $data);
    }

    public function deletePlanMapping($id)
    {
        return $this->planMappingRepository->delete($id);
    }

    public function syncPlanMappingsForLocation($locationId, array $inventories)
    {
        // Delete existing mappings for this location
        // $this->planMappingRepository->deleteByLocationId($locationId);
        $existingPriceIds = $this->getPlanMappingsByLocationId($locationId)->pluck('price_id')->toArray();

        // Create new mappings
        $mappings = [];
        foreach ($inventories as $inventory) {
            $priceId = $inventory->_id;

            if (! in_array($priceId, $existingPriceIds)) {
                $mappings[] = $this->createPlanMapping([
                    'location_id'           => $locationId,
                    'product_id'            => $inventory->product,
                    'product_name'          => $inventory->productName,
                    'price_id'              => $priceId,
                    'price_name'            => $inventory->name,
                    'threshold_amount'      => $inventory->amount ?? 100,
                    'amount_charge_percent' => 2.0,
                ]);
            }
        }

        return $mappings;
    }

    public function getThresholdAmountForPrice($priceId)
    {
        $mapping = $this->getPlanMappingByPriceId($priceId);
        return $mapping ? $mapping->threshold_amount : 0;
    }

    public function getChargePercentForPrice($priceId)
    {
        $mapping = $this->getPlanMappingByPriceId($priceId);
        return $mapping ? $mapping->amount_charge_percent : 2.0;
    }

    public function getPlanMapping()
    {
        return $this->planMappingRepository->getQueryObj();
    }

    public function getAllPlanMappings()
    {
        return $this->planMappingRepository->getAll();
    }

    public function bulkUpdateThresholds($locationId, array $updates)
    {
        foreach ($updates as $priceId => $thresholdAmount) {
            $mapping = $this->planMappingRepository->findByLocationAndPrice($locationId, $priceId);
            if ($mapping) {
                $this->updatePlanMapping($mapping->id, [
                    'threshold_amount' => $thresholdAmount,
                ]);
            }
        }
    }

    public function bulkUpdateChargePercents($locationId, array $updates)
    {
        foreach ($updates as $priceId => $chargePercent) {
            $mapping = $this->planMappingRepository->findByLocationAndPrice($locationId, $priceId);
            if ($mapping) {
                $this->updatePlanMapping($mapping->id, [
                    'amount_charge_percent' => $chargePercent,
                ]);
            }
        }
    }

    // public function fetchInventories($locationId, $request = [])
    // {
    //     $user  = auth()->user();
    //     $token = $user->token ?? null;

    //     // Default response structure
    //     $response = [
    //         'status'      => false,
    //         'message'     => 'Connect to Agency First',
    //         'inventories' => '',
    //         'loadMore'    => false,
    //         'type'        => $token->user_type ?? '',
    //     ];

    //     if (! $token) {
    //         return $response;
    //     }

    //     // Early exit if user type is invalid
    //     if ($token->user_type !== CRM::$lang_com) {
    //         return $response;
    //     }

    //     // Pagination
    //     $limit  = 100;
    //     $offset = $request->offset ?? 0;
    //     if ($offset < 0) {
    //         $offset = 0;
    //     }

    //     // Build query
    //     $query = sprintf(
    //         'products/inventory?altId=%s&altType=location&limit=%d&offset=%d',
    //         $locationId,
    //         $limit,
    //         $offset
    //     );

    //     // Fetch details from agencyV2 API
    //     $detail = CRM::agencyV2($user->id, $query, 'get', '', [], false, $token);

    //     // If response is valid and inventory exists
    //     if ($detail && property_exists($detail, 'inventory')) {
    //         $response['inventories'] = $detail->inventory;
    //         $response['status']      = true;
    //         $response['loadMore']    = count($detail->inventory) >= $limit;

    //         //TODO: add already exist flage
    //         // $ids       = collect($detail)->pluck('id')->toArray();
    //         // $exist_tokens = static::$crm::pluck('location_id')->toArray();
    //         // foreach ($detail as $det) {
    //         //     $det->already_exist = in_array($det->id, $exist_tokens);
    //         // }
    //     }
    //     else{

    //     }

    //     return $response;
    // }

    // public function fetchInventories($locationId, $request = null)
    // {
    //     $user  = auth()->user();
    //     $token = $user->token ?? null;

    //     // Default response structure
    //     $response = [
    //         'status'   => false,
    //         'message'  => 'Connect to Agency First',
    //         'detail'   => [],
    //         'loadMore' => false,
    //         'type'     => $token->user_type ?? null,
    //     ];

    //     // If no token, return default response immediately
    //     if (! $token) {
    //         return $response;
    //     }

    //     $type   = $token->user_type;
    //     $limit  = 100;
    //     $offset = (int) ($request->offset ?? 0);

    //     // If user type is not allowed, return early
    //     if ($type !== self::$lang_com) {
    //         $response['type'] = $type;
    //         return $response;
    //     }

    //     // Build query
    //     $query = sprintf(
    //         'products/inventory?altId=%s&altType=location&limit=%d&offset=%d',
    //         $locationId,
    //         $limit,
    //         $offset
    //     );

    //     try {
    //         // Call API
    //         $apiResponse = self::agencyV2($user->id, $query, 'get', '', [], false, $token);

    //         // If API returned an error
    //         if (! $apiResponse || isset($apiResponse->error)) {
    //             $errorMsg = data_get($apiResponse, 'error.message', 'Unable to fetch inventory');
    //             Log::error("Inventory API Error", [
    //                 'locationId' => $locationId,
    //                 'error'      => $errorMsg,
    //             ]);
    //             $response['message'] = $errorMsg;
    //             return $response;
    //         }

    //         // If inventory exists in response
    //         if (! empty($apiResponse->inventory)) {
    //             $inventoryData        = $apiResponse->inventory;
    //             $response['status']   = true;
    //             $response['detail']   = $inventoryData;
    //             $response['loadMore'] = count($inventoryData) >= $limit;
    //             $response['message']  = 'Inventories fetched successfully';
    //         } else {
    //             // No inventory found
    //             $response['message'] = 'No inventory found for this location';
    //         }
    //     } catch (\Throwable $e) {
    //         // Catch unexpected errors
    //         Log::error("Inventory Fetch Exception", [
    //             'locationId' => $locationId,
    //             'error'      => $e->getMessage(),
    //         ]);
    //         $response['message'] = 'Something went wrong while fetching inventories';
    //     }

    //     return $response;
    // }
}
