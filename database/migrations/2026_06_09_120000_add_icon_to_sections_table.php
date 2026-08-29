<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sections') || Schema::hasColumn('sections', 'icon')) {
            return;
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->string('icon', 100)->default('bi-collection')->after('section_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sections') || ! Schema::hasColumn('sections', 'icon')) {
            return;
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
