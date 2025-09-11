<?php
namespace App\Repositories;

use App\Models\UserSetting;

class UserSettingRepository
{
    protected $model;

    public function __construct(UserSetting $model)
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

    public function findByUserId($userId)
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function update($id, array $data)
    {
        $userSetting = $this->findById($id);
        if ($userSetting) {
            $userSetting->update($data);
            return $userSetting;
        }
        return null;
    }

    public function delete($id)
    {
        $userSetting = $this->findById($id);
        if ($userSetting) {
            return $userSetting->delete();
        }
        return false;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function findWithPaymentMethods()
    {
        return $this->model->whereNotNull('payment_method_id')->get();
    }

    public function findByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByCustomerId($customerId)
    {
        return $this->model->where('customer_id', $customerId)->first();
    }

    public function findByPaymentMethodId($paymentMethodId)
    {
        return $this->model->where('payment_method_id', $paymentMethodId)->first();
    }
}
