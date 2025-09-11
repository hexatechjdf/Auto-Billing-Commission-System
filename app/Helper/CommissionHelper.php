<?php
namespace App\Helpers;

use Carbon\Carbon;

class CommissionHelper
{
    /**
     * Calculate commission amount based on order amount and commission percentage
     */
    public static function calculateCommission($orderAmount, $commissionPercentage)
    {
        return ($orderAmount * $commissionPercentage) / 100;
    }

    /**
     * Calculate total commission for multiple orders
     */
    public static function calculateTotalCommission($orders, $commissionPercentage)
    {
        $totalAmount = 0;
        foreach ($orders as $order) {
            $totalAmount += $order->amount;
        }
        return self::calculateCommission($totalAmount, $commissionPercentage);
    }

    /**
     * Check if commission meets minimum threshold
     */
    public static function meetsMinimumThreshold($commissionAmount, $thresholdAmount)
    {
        return $commissionAmount >= $thresholdAmount;
    }

    /**
     * Calculate shortfall amount if commission doesn't meet threshold
     */
    public static function calculateShortfall($commissionAmount, $thresholdAmount)
    {
        if ($commissionAmount >= $thresholdAmount) {
            return 0;
        }
        return $thresholdAmount - $commissionAmount;
    }

    /**
     * Get date range for commission calculation (last 5 days)
     */
    public static function getCommissionPeriod()
    {
        $endDate   = Carbon::now();
        $startDate = Carbon::now()->subDays(5);

        return [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];
    }

    /**
     * Get monthly date range for minimum threshold check
     */
    public static function getMonthlyPeriod($month = null, $year = null)
    {
        if (! $month) {
            $month = Carbon::now()->month;
        }
        if (! $year) {
            $year = Carbon::now()->year;
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        return [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];
    }

    /**
     * Format commission amount for display
     */
    public static function formatCommission($amount)
    {
        return '$' . number_format($amount, 2);
    }

    /**
     * Validate commission percentage
     */
    public static function isValidCommissionPercentage($percentage)
    {
        return is_numeric($percentage) && $percentage >= 0 && $percentage <= 100;
    }

    /**
     * Calculate commission with minimum charge
     */
    public static function calculateCommissionWithMinimum($orderAmount, $commissionPercentage, $minimumCharge = 0)
    {
        $commission = self::calculateCommission($orderAmount, $commissionPercentage);
        return max($commission, $minimumCharge);
    }
}
