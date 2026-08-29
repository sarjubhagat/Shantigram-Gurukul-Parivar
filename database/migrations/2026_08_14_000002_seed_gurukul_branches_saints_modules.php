<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $sectionQuery = DB::table('sections')->where('section_name', 'Parivar');
        if (Schema::hasColumn('sections', 'deleted_at')) {
            $sectionQuery->whereNull('deleted_at');
        }
        $sectionId = $sectionQuery->value('id');

        if (! $sectionId) {
            $order = ((int) DB::table('sections')->max('order')) + 10;
            $section = [
                'section_name' => 'Parivar',
                'order'        => $order ?: 10,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
            if (Schema::hasColumn('sections', 'icon')) {
                $section['icon'] = 'bi-house-heart-fill';
            }
            $sectionId = DB::table('sections')->insertGetId($section);
        }

        $modules = [
            [
                'name'             => 'gurukul-branches',
                'title'            => 'Branches',
                'controller_path'  => 'Parivar',
                'controller_class' => 'GurukulBranchController',
                'model_class'      => 'GurukulBranch',
                'table_name'       => 'gurukul_branches',
                'icon'             => 'bi-building',
                'sort_order'       => 2,
            ],
            [
                'name'             => 'gurukul-saints',
                'title'            => 'Saints',
                'controller_path'  => 'Parivar',
                'controller_class' => 'GurukulSaintController',
                'model_class'      => 'GurukulSaint',
                'table_name'       => 'gurukul_saints',
                'icon'             => 'bi-person-badge',
                'sort_order'       => 3,
            ],
        ];

        $allPermIds = [];

        foreach ($modules as $mod) {
            $existingId = DB::table('modules')->where('name', $mod['name'])->value('id');

            $payload = array_merge($mod, [
                'section_id' => $sectionId,
                'is_admin'   => true,
                'is_active'  => true,
                'updated_at' => $now,
            ]);

            if ($existingId) {
                DB::table('modules')->where('id', $existingId)->update($payload);
                $moduleId = $existingId;
            } else {
                $payload['created_at'] = $now;
                $moduleId = DB::table('modules')->insertGetId($payload);
            }

            foreach (['list', 'create', 'edit', 'delete'] as $action) {
                $permName = $mod['name'] . '-' . $action;
                $permId = DB::table('permissions')
                    ->where('name', $permName)
                    ->where('guard_name', 'web')
                    ->value('id');

                if (! $permId) {
                    $permId = DB::table('permissions')->insertGetId([
                        'name'       => $permName,
                        'guard_name' => 'web',
                        'module_id'  => $moduleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('permissions')->where('id', $permId)->update([
                        'module_id'  => $moduleId,
                        'updated_at' => $now,
                    ]);
                }

                $allPermIds[] = $permId;
            }
        }

        $roleIds = DB::table('roles')
            ->where(function ($q) {
                $q->whereIn('unique_key', ['super-admin', 'admin'])
                    ->orWhereIn('name', ['super-admin', 'admin', 'Super Admin']);
            })
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($allPermIds as $permId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        $names = ['gurukul-branches', 'gurukul-saints'];

        foreach ($names as $name) {
            $moduleId = DB::table('modules')->where('name', $name)->value('id');
            if (! $moduleId) {
                continue;
            }

            $permIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
            if ($permIds->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
            }
            DB::table('permissions')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
