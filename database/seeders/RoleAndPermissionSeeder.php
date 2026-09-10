<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions (Spatie caches them for performance)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Product & Inventory
            'product.view', 'product.create', 'product.update', 'product.delete',
            'inventory.view', 'inventory.adjust',
            // Orders & POS
            'order.view', 'order.update-status',
            'pos.access', 'pos.create-order',
            // Reports
            'report.view', 'report.export',
            // User & role management
            'user.manage', 'role.manage',
            // Security
            'activity-log.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $customer = Role::firstOrCreate(['name' => 'customer']);

        // Superadmin & Admin get everything
        $superadmin->syncPermissions(Permission::all());
        $admin->syncPermissions(Permission::all());

        // Staff: operational permissions only — including POS operations
        $staff->syncPermissions([
            'product.view', 'product.create', 'product.update',
            'inventory.view', 'inventory.adjust',
            'order.view', 'order.update-status',
            'pos.access', 'pos.create-order',
            'report.view',
        ]);

        // Customer: no backend permissions — they interact through the storefront only
        $customer->syncPermissions([]);

        // Default admin account — CHANGE THIS PASSWORD IMMEDIATELY after first login.
        // Never leave seeded credentials in a production/demo environment.
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@inventory-system.test'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('ChangeMe123!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        // Default POS Walk-in Customer account
        $walkinUser = User::firstOrCreate(
            ['email' => 'walkin@pos.local'],
            [
                'name' => 'Walk-in Customer',
                'password' => Hash::make('WalkinCustomerPos123!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $walkinUser->assignRole('customer');
    }
}
