<?php
namespace App\Services;

use App\Helper\CRM;
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

    public function fetchInventories($locationId, $request = [])
    {
                                 //Important TODO:
        $user  = auth()->user(); // TODO: get the admin user instead of login user (if also used in job) or pass user fonm job
        $token = $user->token ?? null;

        // Default response structure
        $response = [
            'status'      => false,
            'message'     => 'Connect to Agency First',
            'inventories' => [],
            'loadMore'    => false,
            'type'        => $token->user_type ?? null,
        ];

        // If no token, return default response immediately
        if (! $token) {
            return $response;
        }

        $type   = $token->user_type;
        $limit  = 100;
        $offset = (int) ($request['offset'] ?? 0);

        // If user type is not allowed, return early
        if ($type !== CRM::$lang_com) {
            $response['type'] = $type;
            return $response;
        }

        // Build API query
        $query = sprintf(
            'products/inventory?altId=%s&altType=location&limit=%d&offset=%d',
            $locationId,
            $limit,
            $offset
        );

        // Call API (may throw exception → handled in controller)
        $apiResponse = CRM::agencyV2($user->id, $query, 'get', '', [], false, $token);

        // If API returned an error
        if (! $apiResponse || isset($apiResponse->error)) {
            $errorMsg = data_get($apiResponse, 'error.message', 'Unable to fetch inventory');
            Log::error("Inventory API Error", [
                'locationId' => $locationId,
                'error'      => $errorMsg,
            ]);
            $response['message'] = $errorMsg;
            return $response;
        }

        // If inventory exists in response
        if (! empty($apiResponse->inventory)) {
            $inventoryData           = $apiResponse->inventory;
            $response['status']      = true;
            $response['inventories'] = $inventoryData;
            $response['loadMore']    = count($inventoryData) >= $limit;
            $response['message']     = 'Inventories fetched successfully';
            //TODO: add already exist flage
        } else {
            // No inventory found
            $response['message'] = 'No inventory found for this location';
        }

        return $response;
    }
}
