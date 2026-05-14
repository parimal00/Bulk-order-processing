<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $dummyCustomers = [
            [
                'code' => 'CUST-0001',
                'name' => 'Acme Retail Pvt Ltd',
                'tier' => 'standard',
                'email' => 'orders@acmeretail.test',
                'phone' => '+15551000001',
                'is_active' => true,
            ],
            [
                'code' => 'CUST-0002',
                'name' => 'Himal Trade House',
                'tier' => 'gold',
                'email' => 'procurement@himaltrade.test',
                'phone' => '+15551000002',
                'is_active' => true,
            ],
            [
                'code' => 'CUST-0003',
                'name' => 'Everest Mart Chain',
                'tier' => 'silver',
                'email' => 'buyers@everestmart.test',
                'phone' => '+15551000003',
                'is_active' => true,
            ],
        ];

        foreach ($dummyCustomers as $customer) {
            Customer::query()->updateOrCreate(
                ['code' => $customer['code']],
                $customer
            );
        }

        Customer::factory()->count(7)->create();
    }
}
