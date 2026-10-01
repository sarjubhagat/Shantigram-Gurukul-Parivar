<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relations', function (Blueprint $table) {
            $table->id();
            $table->string('relation', 100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('relation');
            $table->index('is_active');
        });

        $now = now();
        $defaults = [
            'Father',
            'Mother',
            'Son',
            'Daughter',
            'Brother',
            'Sister',
            'Husband',
            'Wife',
            'Grandfather',
            'Grandmother',
            'Uncle',
            'Aunt',
            'Nephew',
            'Niece',
            'Cousin',
            'Father-in-law',
            'Mother-in-law',
            'Son-in-law',
            'Daughter-in-law',
            'Brother-in-law',
            'Sister-in-law',
            'Friend',
            'Other',
        ];

        foreach ($defaults as $name) {
            DB::table('relations')->insert([
                'relation'   => $name,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sectionQuery = DB::table('sections')->where('section_name', 'Parivar');
        if (Schema::hasColumn('sections', 'deleted_at')) {
            $sectionQuery->whereNull('deleted_at');
        }
        $sectionId = $sectionQuery->value('id');

        if (! $sectionId) {
            $section = [
                'section_name' => 'Parivar',
                'order'        => ((int) DB::table('sections')->max('order')) + 10,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
            if (Schema::hasColumn('sections', 'icon')) {
                $section['icon'] = 'bi-house-heart-fill';
            }
            $sectionId = DB::table('sections')->insertGetId($section);
        }

        $existing = DB::table('modules')->where('name', 'relations')->value('id');
        $payload = [
            'section_id'       => $sectionId,
            'name'             => 'relations',
            'title'            => 'Relations',
            'controller_path'  => 'Parivar',
            'controller_class' => 'RelationController',
            'model_class'      => 'Relation',
            'table_name'       => 'relations',
            'icon'             => 'bi-people',
            'sort_order'       => 7,
            'is_admin'         => true,
            'is_active'        => true,
            'updated_at'       => $now,
        ];

        if ($existing) {
            DB::table('modules')->where('id', $existing)->update($payload);
            $moduleId = $existing;
        } else {
            $payload['created_at'] = $now;
            $moduleId = DB::table('modules')->insertGetId($payload);
        }

        $permIds = [];
        foreach (['list', 'create', 'edit', 'delete'] as $action) {
            $name = 'relations-' . $action;
            $permId = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            if (! $permId) {
                $permId = DB::table('permissions')->insertGetId([
                    'name'       => $name,
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
            $permIds[] = $permId;
        }

        $roleIds = DB::table('roles')
            ->where(function ($q) {
                $q->whereIn('unique_key', ['super-admin', 'admin'])
                    ->orWhereIn('name', ['super-admin', 'admin', 'Super Admin']);
            })
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
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
        $moduleId = DB::table('modules')->where('name', 'relations')->value('id');
        if ($moduleId) {
            $permIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
            if ($permIds->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
            }
            DB::table('permissions')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }

        Schema::dropIfExists('relations');

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
