<?php
namespace App\Repositories;

use App\Models\Order;

class OrderRepository
{
    protected $model;

    public function __construct(Order $model)
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

    public function findByStatus($status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function updateStatus($id, $status)
    {
        $order = $this->findById($id);
        if ($order) {
            $order->status = $status;
            $order->save();
            return $order;
        }
        return null;
    }

    public function findForCommissionCalculation($locationId, $startDate, $endDate)
    {
        return $this->model
            ->where('location_id', $locationId)
            ->where('status', 'succeeded')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function update($id, array $data)
    {
        $order = $this->findById($id);
        if ($order) {
            $order->update($data);
            return $order;
        }
        return null;
    }

    public function delete($id)
    {
        $order = $this->findById($id);
        if ($order) {
            return $order->delete();
        }
        return false;
    }
}
