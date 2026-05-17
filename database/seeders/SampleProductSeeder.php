<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class SampleProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'RICE-BASMATI-5KG',
                'name' => 'Basmati Rice 5KG',
                'description' => 'Premium Basmati Rice 5KG pack',
                'unit_of_measure' => 'Bag',
                'moq' => 1,
                'pack_size' => 1,
                'base_price' => 15.50,
                'is_active' => true,
            ],
            [
                'sku' => 'RICE-BASMATI-25KG',
                'name' => 'Basmati Rice 25KG',
                'description' => 'Premium Basmati Rice 25KG pack',
                'unit_of_measure' => 'Bag',
                'moq' => 1,
                'pack_size' => 1,
                'base_price' => 70.00,
                'is_active' => true,
            ],
            [
                'sku' => 'SOAP-LEMON-100G',
                'name' => 'Lemon Soap 100G',
                'description' => 'Fresh Lemon Scented Soap',
                'unit_of_measure' => 'Piece',
                'moq' => 10,
                'pack_size' => 10,
                'base_price' => 1.20,
                'is_active' => true,
            ],
            [
                'sku' => 'TEA-CTC-1KG',
                'name' => 'CTC Tea 1KG',
                'description' => 'Strong CTC Tea',
                'unit_of_measure' => 'Bag',
                'moq' => 5,
                'pack_size' => 5,
                'base_price' => 8.00,
                'is_active' => true,
            ],
            [
                'sku' => 'OIL-SUNFLOWER-1L',
                'name' => 'Sunflower Oil 1L',
                'description' => 'Pure Sunflower Oil',
                'unit_of_measure' => 'Bottle',
                'moq' => 6,
                'pack_size' => 6,
                'base_price' => 4.50,
                'is_active' => true,
            ]
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
