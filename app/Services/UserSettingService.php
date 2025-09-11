<?php
namespace App\Services;

use App\Repositories\UserSettingRepository;

class UserSettingService
{
    protected $userSettingRepository;

    public function __construct(UserSettingRepository $userSettingRepository)
    {
        $this->userSettingRepository = $userSettingRepository;
    }

    public function createUserSetting(array $data)
    {
        return $this->userSettingRepository->create($data);
    }

    public function getUserSettingById($id)
    {
        return $this->userSettingRepository->findById($id);
    }

    public function getUserSettingByUserId($userId)
    {
        return $this->userSettingRepository->findByUserId($userId);
    }

    public function updateUserSetting($id, array $data)
    {
        return $this->userSettingRepository->update($id, $data);
    }

    public function updatePaymentMethod($userId, $paymentMethodId, $customerId)
    {
        $userSetting = $this->getUserSettingByUserId($userId);
        if ($userSetting) {
            return $this->updateUserSetting($userSetting->id, [
                'payment_method_id' => $paymentMethodId,
                'customer_id'       => $customerId,
            ]);
        }
        return null;
    }

    public function updateEmail($userId, $email)
    {
        $userSetting = $this->getUserSettingByUserId($userId);
        if ($userSetting) {
            return $this->updateUserSetting($userSetting->id, [
                'email' => $email,
            ]);
        }
        return null;
    }

    public function clearPaymentMethod($userId)
    {
        $userSetting = $this->getUserSettingByUserId($userId);
        if ($userSetting) {
            return $this->updateUserSetting($userSetting->id, [
                'payment_method_id' => null,
                'customer_id'       => null,
            ]);
        }
        return null;
    }

    public function getAllUserSettings()
    {
        return $this->userSettingRepository->getAll();
    }

    public function getUsersWithPaymentMethods()
    {
        return $this->userSettingRepository->findWithPaymentMethods();
    }
}
