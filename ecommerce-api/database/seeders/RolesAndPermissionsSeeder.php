<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define product permissions
        Permission::create(['name' => 'view products']);
        Permission::create(['name' => 'create products']);
        Permission::create(['name' => 'edit products']);
        Permission::create(['name' => 'delete products']);

        // Define order permissions
        Permission::create(['name' => 'view orders']);
        Permission::create(['name' => 'create orders']);
        Permission::create(['name' => 'update orders']);
        Permission::create(['name' => 'cancel orders']);

        // Define user permissions
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'edit users']);

        // Define delivery permissions
        Permission::create(['name' => 'view deliveries']);
        Permission::create(['name' => 'update delivery status']); // pendding, shipped, delivered

        // Create Admin role and assign all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view orders',
            'create orders',
            'update orders',
            'cancel orders',
            'view users',
            'edit users',
            'view deliveries',
            'update delivery status',
        ]);

        // Create Customer role with limited permissions
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'view products',
            'view orders',
            'create orders',
            'cancel orders'
        ]);

        // Create Delivery role
        $deliveryRole = Role::create(['name' => 'delivery']);
        $deliveryRole->givePermissionTo([
            'view deliveries',
            'update delivery status',
            'view orders',
            'view products'
        ]);
    }
}
