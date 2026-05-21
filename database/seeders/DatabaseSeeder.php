<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            CustomerSeeder::class,
            SampleProductSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Manual Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
            'role' => 'approver',
        ]);

        User::factory()->create([
            'name' => 'Ops Operator',
            'email' => 'ops@example.com',
            'password' => bcrypt('password'),
            'role' => 'ops',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'ops',
        ]);
    }
}
