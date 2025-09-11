<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class PaymentHelper
{
    /**
     * Format amount for Stripe (convert to cents)
     */
    public static function formatAmountForStripe($amount)
    {
        return intval($amount * 100);
    }

    /**
     * Format amount from Stripe (convert from cents)
     */
    public static function formatAmountFromStripe($amount)
    {
        return $amount / 100;
    }

    /**
     * Generate payment description
     */
    public static function generatePaymentDescription($type, $locationId, $period = null)
    {
        switch ($type) {
            case 'commission':
                return "Commission charge for location {$locationId}" . ($period ? " - {$period}" : '');
            case 'minimum_threshold':
                return "Minimum threshold charge for location {$locationId}" . ($period ? " - {$period}" : '');
            default:
                return "Payment for location {$locationId}";
        }
    }

    /**
     * Validate payment amount
     */
    public static function isValidAmount($amount)
    {
        return is_numeric($amount) && $amount > 0;
    }

    /**
     * Generate payment metadata
     */
    public static function generatePaymentMetadata($locationId, $type, $additionalData = [])
    {
        return array_merge([
            'location_id'  => $locationId,
            'payment_type' => $type,
            'created_at'   => now()->toISOString(),
        ], $additionalData);
    }

    /**
     * Check if payment method is valid
     */
    public static function isValidPaymentMethod($paymentMethodId)
    {
        return ! empty($paymentMethodId) && is_string($paymentMethodId);
    }

    /**
     * Generate invoice line item
     */
    public static function generateInvoiceLineItem($description, $amount, $quantity = 1)
    {
        return [
            'price_data' => [
                'currency'     => 'usd',
                'product_data' => [
                    'name' => $description,
                ],
                'unit_amount'  => self::formatAmountForStripe($amount),
            ],
            'quantity'   => $quantity,
        ];
    }

    /**
     * Calculate payment fees (if applicable)
     */
    public static function calculatePaymentFees($amount, $feePercentage = 0, $fixedFee = 0)
    {
        $percentageFee = ($amount * $feePercentage) / 100;
        return $percentageFee + $fixedFee;
    }

    /**
     * Format payment status for display
     */
    public static function formatPaymentStatus($status)
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Check if payment is successful
     */
    public static function isPaymentSuccessful($status)
    {
        return in_array($status, ['succeeded', 'paid', 'completed']);
    }

    /**
     * Check if payment failed
     */
    public static function isPaymentFailed($status)
    {
        return in_array($status, ['failed', 'declined', 'canceled']);
    }

    /**
     * Check if payment is pending
     */
    public static function isPaymentPending($status)
    {
        return in_array($status, ['pending', 'processing', 'requires_action']);
    }

    /**
     * Log payment event
     */
    public static function logPaymentEvent($event, $paymentId, $amount, $status, $additionalData = [])
    {
        Log::info("Payment {$event}", array_merge([
            'payment_id' => $paymentId,
            'amount'     => $amount,
            'status'     => $status,
            'timestamp'  => now(),
        ], $additionalData));
    }
}
