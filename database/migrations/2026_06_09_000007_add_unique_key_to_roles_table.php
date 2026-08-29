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
            $table->string('unique_key', 100)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        $table = config('permission.table_names.roles');

        Schema::table($table, function (Blueprint $table) {
            $table->dropUnique(['unique_key']);
            $table->dropColumn('unique_key');
        });
    }
};
