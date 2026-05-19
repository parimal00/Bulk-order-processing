<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FMCG Pricing Rules Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the discount percentages for different customer
    | tiers, volume thresholds, and default pricing margins.
    |
    */

    'pricing' => [
        // Discount percentage by customer tier
        'tiers' => [
            'gold' => 10,   // 10% off
            'silver' => 5,  // 5% off
            'standard' => 0 // 0% off
        ],

        // Default profit margin percentage for products
        'default_margin' => 22,

        // Volume-based discount configurations
        'volume_discounts' => [
            'enabled' => true,
            'threshold' => 100, // Quantity required
            'discount' => 5,    // Additional 5% off
        ]
    ]
];
