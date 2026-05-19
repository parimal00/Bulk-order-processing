<?php

namespace App\Services\Fmcg;

use App\Models\Customer;
use App\Models\Product;

class PricingEngine
{
    /**
     * Calculate the final unit price and return any policy flags.
     * 
     * @return array{unit_price: float, flags: string[]}
     */
    public function calculate(Customer $customer, Product $product, int $quantity): array
    {
        $basePrice = $product->base_price;
        $discountPercent = 0;
        $flags = [];

        // 1. Customer Tier Discount
        $tier = strtolower($customer->type);
        $tierDiscounts = config('fmcg.pricing.tiers', []);
        $discountPercent += $tierDiscounts[$tier] ?? 0;

        // 2. Volume Discount
        $volumeConfig = config('fmcg.pricing.volume_discounts', []);
        if (($volumeConfig['enabled'] ?? false) && $quantity >= ($volumeConfig['threshold'] ?? 100)) {
            $discountPercent += $volumeConfig['discount'] ?? 5;
            $flags[] = "Volume Discount Applied (>= " . ($volumeConfig['threshold'] ?? 100) . " qty)";
        }

        // Calculate final unit price
        $unitPrice = $basePrice * (1 - ($discountPercent / 100));
        
        // Ensure price doesn't go negative
        $unitPrice = max(0, $unitPrice);

        $defaultMargin = config('fmcg.pricing.default_margin', 22);

        return [
            'unit_price' => round($unitPrice, 2),
            'flags' => $flags,
            'margin' => $discountPercent > 0 ? ($defaultMargin - $discountPercent) . '%' : $defaultMargin . '%',
        ];
    }
}
