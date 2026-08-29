<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_info', function (Blueprint $table) {
            $table->string('aadhaar_number', 12)->nullable()->after('unique_id');
            $table->unique('aadhaar_number');
        });
    }

    public function down(): void
    {
        Schema::table('personal_info', function (Blueprint $table) {
            $table->dropUnique(['aadhaar_number']);
            $table->dropColumn('aadhaar_number');
        });
    }
};
