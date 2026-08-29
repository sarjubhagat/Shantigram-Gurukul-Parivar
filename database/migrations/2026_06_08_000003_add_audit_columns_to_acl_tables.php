<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        // Roles table
        $rolesTable = config('permission.table_names.roles');
        Schema::table($rolesTable, function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('guard_name');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        // Permissions table
        $permissionsTable = config('permission.table_names.permissions');
        Schema::table($permissionsTable, function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('guard_name');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        $rolesTable = config('permission.table_names.roles');
        Schema::table($rolesTable, function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        $permissionsTable = config('permission.table_names.permissions');
        Schema::table($permissionsTable, function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });
    }
};
