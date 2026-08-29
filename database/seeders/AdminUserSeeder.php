<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create super-admin role
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // Create the user
        $user = User::updateOrCreate(
            ['email' => 'sarajubhagat@gmail.com'],
            [
                'name'     => 'Saraju Bhagat',
                'password' => 'Gurukul@123',
            ]
        );

        // Assign role
        $user->syncRoles([$role->name]);

        $this->command->info('Admin user created: sarajubhagat@gmail.com / Gurukul@123');
    }
}
