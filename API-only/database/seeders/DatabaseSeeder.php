<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // call the User seeder
        /* User::factory(10)->create(); */
        // call the posts seeder to seed the posts table
        /* $this->call(PostSeeder::class);
 */
        /* User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */

        // role and permission seeder

        $AdminUser = User::create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password'),
        ]);
        $EditorUser = User::create([
            'name' => 'editor',
            'email' => 'editor@mail.com',
            'password' => Hash::make('password'),
        ]);

        $adminRole = Role::create(['name' => 'admin']);
        $editorRole = Role::create(['name' => 'editor']);

        $editPermission = Permission::create(['name' => 'edit-post']);
        $publishPermission = Permission::create(['name' => 'publish-post']);

        $adminRole->permissions()->attach([$editPermission, $publishPermission]);
        $editorRole->permissions()->attach($editPermission);

        $AdminUser->roles()->attach($adminRole);
        $EditorUser->roles()->attach($editorRole);

        // call the ProductSeeder to seed the products table

    }
}
