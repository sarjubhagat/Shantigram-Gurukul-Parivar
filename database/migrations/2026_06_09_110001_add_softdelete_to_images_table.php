<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('images') || Schema::hasColumn('images', 'deleted_at')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        // Migrate legacy status-based deletes to soft deletes.
        if (Schema::hasColumn('images', 'processing_status')) {
            DB::table('images')
                ->where('processing_status', 'deleted')
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('images') || ! Schema::hasColumn('images', 'deleted_at')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
