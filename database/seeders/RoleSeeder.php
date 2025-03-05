<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrator with full access'],
            ['name' => 'sales', 'description' => 'Sales staff with access to orders'],
            ['name' => 'inventory', 'description' => 'Inventory staff with access to products'],
            ['name' => 'finance', 'description' => 'Finance staff with access to budgets'],
            ['name' => 'hr', 'description' => 'HR staff with access to employees'],
            ['name' => 'it', 'description' => 'IT staff with access to security alerts'],
            ['name' => 'user', 'description' => 'Regular user with limited access'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}