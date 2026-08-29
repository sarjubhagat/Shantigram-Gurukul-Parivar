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
        Schema::create('personal_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('unique_id')->unique();
            $table->string('en_first_name');
            $table->string('en_last_name');
            $table->string('en_middle_name')->nullable();
            $table->string('en_grandfather_name')->nullable();
            $table->string('gu_first_name')->nullable();
            $table->string('gu_last_name')->nullable();
            $table->string('gu_middle_name')->nullable();
            $table->string('gu_grandfather_name')->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('c_code_1', 10)->nullable();
            $table->string('phone_1', 20)->nullable();
            $table->string('c_code_2', 10)->nullable();
            $table->string('phone_2', 20)->nullable();
            $table->string('native_place')->nullable();
            $table->boolean('is_gurukul_student')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('en_last_name');
            $table->index('phone_1');
        });

        Schema::create('business_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('unique_id');
            $table->string('business_name');
            $table->text('business_address')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('unique_id')
                ->references('unique_id')
                ->on('personal_info')
                ->cascadeOnDelete();
        });

        Schema::create('gurukul_branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gurukul_saints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gurukul_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('unique_id');
            $table->foreignId('branch_id')->nullable()->constrained('gurukul_branches')->nullOnDelete();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->unsignedSmallInteger('total_year')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('unique_id')
                ->references('unique_id')
                ->on('personal_info')
                ->cascadeOnDelete();
        });

        Schema::create('connected_saints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gurukul_info_id')->constrained('gurukul_info')->cascadeOnDelete();
            $table->foreignId('saints_id')->constrained('gurukul_saints')->cascadeOnDelete();
            $table->unique(['gurukul_info_id', 'saints_id']);
        });

        $this->seedModule();
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('name', 'parivar')->value('id');

        if ($moduleId) {
            $permIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
            if ($permIds->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
            }
            DB::table('permissions')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }

        DB::table('sections')->where('section_name', 'Parivar')->delete();

        Schema::dropIfExists('connected_saints');
        Schema::dropIfExists('gurukul_info');
        Schema::dropIfExists('gurukul_saints');
        Schema::dropIfExists('gurukul_branches');
        Schema::dropIfExists('business_info');
        Schema::dropIfExists('personal_info');

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

        $existing = DB::table('modules')->where('name', 'parivar')->value('id');

        if (! $existing) {
            $module = [
                'section_id'       => $sectionId,
                'name'             => 'parivar',
                'title'            => 'Parivar',
                'controller_path'  => 'Parivar',
                'controller_class' => 'ParivarController',
                'model_class'      => 'PersonalInfo',
                'table_name'       => 'personal_info',
                'icon'             => 'bi-people-fill',
                'sort_order'       => 1,
                'is_admin'         => true,
                'is_active'        => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $moduleId = DB::table('modules')->insertGetId($module);
        } else {
            $moduleId = $existing;
            DB::table('modules')->where('id', $moduleId)->update([
                'section_id'       => $sectionId,
                'title'            => 'Parivar',
                'controller_path'  => 'Parivar',
                'controller_class' => 'ParivarController',
                'model_class'      => 'PersonalInfo',
                'table_name'       => 'personal_info',
                'icon'             => 'bi-people-fill',
                'is_admin'         => true,
                'is_active'        => true,
                'updated_at'       => $now,
            ]);
        }

        $permIds = [];
        foreach (['list', 'create', 'edit', 'delete'] as $action) {
            $name = 'parivar-' . $action;
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
