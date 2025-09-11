<?php
namespace App\Services;

use App\Repositories\OrderRepository;

class OrderService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function createOrder(array $data)
    {
        return $this->orderRepository->create($data);
    }

    public function getOrderById($id)
    {
        return $this->orderRepository->findById($id);
    }

    public function getOrdersByLocationId($locationId)
    {
        return $this->orderRepository->findByLocationId($locationId);
    }

    public function getOrdersByStatus($status)
    {
        return $this->orderRepository->findByStatus($status);
    }

    public function updateOrderStatus($id, $status)
    {
        return $this->orderRepository->updateStatus($id, $status);
    }

    public function getOrdersForCommissionCalculation($locationId, $startDate, $endDate)
    {
        return $this->orderRepository->findForCommissionCalculation($locationId, $startDate, $endDate);
    }
}
