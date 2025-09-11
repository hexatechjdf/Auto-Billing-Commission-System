<?php
namespace App\Repositories;

use App\Models\PlanMapping;

class PlanMappingRepository
{
    protected $model;

    public function __construct(PlanMapping $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function findByLocationId($locationId)
    {
        return $this->model->where('location_id', $locationId)->get();
    }

    public function findByPriceId($priceId)
    {
        return $this->model->where('price_id', $priceId)->first();
    }

    public function findByLocationAndPrice($locationId, $priceId)
    {
        return $this->model->where('location_id', $locationId)
            ->where('price_id', $priceId)
            ->first();
    }

    public function update($id, array $data)
    {
        $planMapping = $this->findById($id);
        if ($planMapping) {
            $planMapping->update($data);
            return $planMapping;
        }
        return null;
    }

    public function delete($id)
    {
        $planMapping = $this->findById($id);
        if ($planMapping) {
            return $planMapping->delete();
        }
        return false;
    }

    public function deleteByLocationId($locationId)
    {
        return $this->model->where('location_id', $locationId)->delete();
    }

    public function getQueryObj()
    {
        return $this->model->query();
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function findByProductId($productId)
    {
        return $this->model->where('product_id', $productId)->get();
    }

    public function existsForLocationAndPrice($locationId, $priceId)
    {
        return $this->model->where('location_id', $locationId)
            ->where('price_id', $priceId)
            ->exists();
    }

    public function getUniqueLocations()
    {
        return $this->model->distinct('location_id')->pluck('location_id');
    }

    public function getUniqueProducts($locationId = null)
    {
        $query = $this->model->distinct('product_id');

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->pluck('product_id');
    }

    public function bulkInsert(array $data)
    {
        return $this->model->insert($data);
    }
}
