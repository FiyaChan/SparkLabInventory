<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            ProductVariationSeeder::class,
        ]);

        $testCustomer = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test Explorer',
                'password' => \Illuminate\Support\Facades\Hash::make('Password123!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $testCustomer->assignRole('customer');
    }
}
