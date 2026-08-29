<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                  // URL slug + permission prefix  e.g. "users"
            $table->string('title');                         // Display label  e.g. "Users"
            // Subfolder inside app/Http/Controllers/Admin/
            // null  → Admin\UserController
            // "AccessControl" → Admin\AccessControl\RoleController
            $table->string('controller_path')->nullable();
            $table->string('controller_class');              // e.g. "UserController"
            $table->string('model_class')->nullable();       // e.g. "User"
            $table->string('table_name')->nullable();        // e.g. "users"
            $table->string('icon')->default('bi-box-seam'); // Bootstrap icon
            $table->integer('sort_order')->default(0);
            $table->boolean('is_admin')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
