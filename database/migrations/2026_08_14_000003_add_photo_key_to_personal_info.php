<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_info', function (Blueprint $table) {
            $table->string('photo_key')->nullable()->after('is_gurukul_student');
        });
    }

    public function down(): void
    {
        Schema::table('personal_info', function (Blueprint $table) {
            $table->dropColumn('photo_key');
        });
    }
};
