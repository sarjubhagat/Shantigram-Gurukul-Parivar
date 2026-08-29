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
        Schema::create('icard_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        Schema::create('icard_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_key', 32)->unique();
            $table->string('image_path')->nullable();
            $table->json('color_codes')->nullable();
            $table->timestamps();
        });

        Schema::create('icard_qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('unique_id')->unique();
            $table->string('qr_token', 16);
            $table->timestamps();

            $table->foreign('unique_id')
                ->references('unique_id')
                ->on('personal_info')
                ->cascadeOnDelete();
        });

        $now = now();
        DB::table('icard_images')->insert([
            [
                'image_key'   => 'FRONT',
                'image_path'  => null,
                'color_codes' => json_encode(config('icard.colors')),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'image_key'   => 'BACK',
                'image_path'  => null,
                'color_codes' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);

        foreach (config('icard.defaults') as $key => $value) {
            DB::table('icard_settings')->insert([
                'key'   => $key,
                'value' => (string) $value,
            ]);
        }

        $this->seedModule();
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('name', 'icard-print')->value('id');
        if ($moduleId) {
            $permIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
            if ($permIds->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
            }
            DB::table('permissions')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }

        Schema::dropIfExists('icard_qr_tokens');
        Schema::dropIfExists('icard_images');
        Schema::dropIfExists('icard_settings');

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    private function seedModule(): void
    {
        $now = now();

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

        $existing = DB::table('modules')->where('name', 'icard-print')->value('id');
        $payload = [
            'section_id'       => $sectionId,
            'name'             => 'icard-print',
            'title'            => 'I-Card Print',
            'controller_path'  => 'Parivar',
            'controller_class' => 'IcardPrintController',
            'model_class'      => 'PersonalInfo',
            'table_name'       => 'personal_info',
            'icon'             => 'bi-credit-card-2-front',
            'sort_order'       => 4,
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
            $name = 'icard-print-' . $action;
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
};
