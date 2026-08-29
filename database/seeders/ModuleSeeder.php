<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // controller_path = subfolder inside app/Http/Controllers/Admin/
            // Leave null when the controller sits directly in Admin/
            // e.g. null → Admin\UserController
            // e.g. 'AccessControl' → Admin\AccessControl\RoleController
            [
                'name'             => 'users',
                'title'            => 'Users',
                'controller_path'  => 'AccessControl',
                'controller_class' => 'UserController',
                'model_class'      => 'User',
                'table_name'       => 'users',
                'icon'             => 'bi-people-fill',
                'sort_order'       => 1,
                'is_admin'         => true,
                'is_active'        => true,
                'actions'          => ['list', 'create', 'edit', 'delete'],
            ],
            [
                'name'             => 'roles',
                'title'            => 'Roles',
                'controller_path'  => 'AccessControl',
                'controller_class' => 'RoleController',
                'model_class'      => 'Role',
                'table_name'       => 'roles',
                'icon'             => 'bi-shield-lock-fill',
                'sort_order'       => 2,
                'is_admin'         => true,
                'is_active'        => true,
                'actions'          => ['list', 'create', 'edit', 'delete'],
            ],
            [
                'name'             => 'permissions',
                'title'            => 'Permissions',
                'controller_path'  => 'AccessControl',
                'controller_class' => 'PermissionController',
                'model_class'      => 'Permission',
                'table_name'       => 'permissions',
                'icon'             => 'bi-key-fill',
                'sort_order'       => 3,
                'is_admin'         => true,
                'is_active'        => true,
                'actions'          => ['list', 'create', 'delete'],
            ],
            [
                'name'             => 'modules',
                'title'            => 'Modules',
                'controller_path'  => 'AccessControl',
                'controller_class' => 'ModuleController',
                'model_class'      => 'Module',
                'table_name'       => 'modules',
                'icon'             => 'bi-box-seam-fill',
                'sort_order'       => 4,
                'is_admin'         => true,
                'is_active'        => true,
                'actions'          => ['list', 'create', 'edit', 'delete'],
            ],
        ];

        $allPermissions = [];

        foreach ($modules as $data) {
            $actions = $data['actions'];
            unset($data['actions']);

            $module = Module::updateOrCreate(['name' => $data['name']], $data);

            foreach ($actions as $action) {
                $perm = Permission::firstOrCreate([
                    'name'       => $module->name . '-' . $action,
                    'guard_name' => 'web',
                ]);
                $allPermissions[] = $perm->name;
            }

            $this->command->info("  ✓ Module [{$module->title}] — path: {$module->controller_path}\\{$module->controller_class}");
        }

        // Assign all permissions to super-admin
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($allPermissions);

        $this->command->info("  ✓ All permissions assigned to [super-admin]");
    }
}
