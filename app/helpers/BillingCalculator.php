<?php
// app/helpers/BillingCalculator.php

require_once __DIR__ . '/../../config/database.php';

class BillingCalculator {
    public static function calculate(string $accountType, int $previousReading, int $currentReading, float $previousUnpaid = 0.00): array {
        $pdo = Database::getConnection();

        // Fetch active water rate for account type
        $stmt = $pdo->prepare("SELECT * FROM water_rates WHERE account_type = ? AND status = 'Active' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$accountType]);
        $rate = $stmt->fetch();

        if (!$rate) {
            // Default fallbacks if missing
            $rate = [
                'min_consumption' => 10,
                'min_rate' => 180.00,
                'rate_per_m3' => 22.50,
                'penalty_rate' => 10.00
            ];
        }

        $minConsumption = (int)$rate['min_consumption'];
        $minRate = (float)$rate['min_rate'];
        $ratePerM3 = (float)$rate['rate_per_m3'];

        $consumption = max(0, $currentReading - $previousReading);

        if ($consumption <= $minConsumption) {
            $subtotal = $minRate;
        } else {
            $extraCons = $consumption - $minConsumption;
            $subtotal = $minRate + ($extraCons * $ratePerM3);
        }

        $tax = 0.00; // Tax rate setting if needed
        $totalAmount = $subtotal + $tax + $previousUnpaid;

        return [
            'consumption' => $consumption,
            'min_consumption' => $minConsumption,
            'applied_min_rate' => $minRate,
            'applied_rate_per_m3' => $ratePerM3,
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'previous_unpaid' => round($previousUnpaid, 2),
            'total_amount' => round($totalAmount, 2)
        ];
    }
}
