<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.roles');

        Schema::table($table, function (Blueprint $table) {
            $table->softDeletes()->after('guard_name');
        });
    }

    public function down(): void
    {
        $table = config('permission.table_names.roles');

        Schema::table($table, function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
