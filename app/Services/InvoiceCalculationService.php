<?php

namespace App\Services;

class InvoiceCalculationService
{
    /**
     * Calculate item totals based on quantity and unit price
     */
    public static function calculateItemTotals(float $quantity, float $unitPrice, float $taxRate = 0): array
    {
        $totalPrice = $quantity * $unitPrice;
        $totalTaxAmount = $totalPrice * ($taxRate / 100);
        $amountWithTax = $totalPrice + $totalTaxAmount;

        return [
            'total_price' => round($totalPrice, 2),
            'total_tax_amount' => round($totalTaxAmount, 2),
            'amount_with_tax' => round($amountWithTax, 2),
        ];
    }

    /**
     * Calculate tax amounts for given price and rate
     */
    public static function calculateTaxAmounts(float $totalPrice, float $taxRate): array
    {
        $totalTaxAmount = $totalPrice * ($taxRate / 100);
        $amountWithTax = $totalPrice + $totalTaxAmount;

        return [
            'total_tax_amount' => round($totalTaxAmount, 2),
            'amount_with_tax' => round($amountWithTax, 2),
        ];
    }

    /**
     * Group items by tax name and calculate breakdown
     */
    public static function calculateTaxBreakdown(array $items): array
    {
        $taxBreakdown = [];
        $totalAmount = 0;

        foreach ($items as $item) {
            $taxName = $item['tax_name'] ?? 'No Tax';
            $taxAmount = floatval($item['total_tax_amount'] ?? 0);
            $totalAmount += floatval($item['amount_with_tax'] ?? 0);

            if (!isset($taxBreakdown[$taxName])) {
                $taxBreakdown[$taxName] = 0;
            }
            $taxBreakdown[$taxName] += $taxAmount;
        }

        return [
            'breakdown' => $taxBreakdown,
            'total' => $totalAmount,
        ];
    }

    /**
     * Calculate final total including extra charges and discounts
     */
    public static function calculateFinalTotal(array $items, array $extraCharges): array
    {
        $subtotal = array_sum(array_column($items, 'amount_with_tax'));
        $totalAdjustments = 0;

        foreach ($extraCharges as $charge) {
            $amount = floatval($charge['amount'] ?? 0);
            if ($charge['type'] === 'discount') {
                $totalAdjustments -= $amount;
            } else {
                $totalAdjustments += $amount;
            }
        }

        $finalTotal = $subtotal + $totalAdjustments;

        return [
            'subtotal' => round($subtotal, 2),
            'adjustments' => round($totalAdjustments, 2),
            'final_total' => round($finalTotal, 2),
        ];
    }

    /**
     * Validate item data
     */
    public static function validateItemData(array $item): array
    {
        return [
            'item_name' => trim($item['item_name'] ?? ''),
            'quantity' => max(0, floatval($item['quantity'] ?? 0)),
            'unit_price' => max(0, floatval($item['unit_price'] ?? 0)),
            'tax_rate' => max(0, min(100, floatval($item['tax_rate'] ?? 0))), // Cap at 100%
        ];
    }

    /**
     * Validate extra charge data
     */
    public static function validateExtraChargeData(array $charge): array
    {
        return [
            'name' => trim($charge['name'] ?? ''),
            'amount' => max(0, floatval($charge['amount'] ?? 0)),
            'type' => in_array($charge['type'] ?? '', ['discount', 'extra_charge'])
                ? $charge['type']
                : 'extra_charge',
        ];
    }
}
