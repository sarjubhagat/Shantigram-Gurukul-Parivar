<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_key', 64)->unique();
            $table->string('title');
            $table->json('tags')->nullable();
            $table->string('original_key')->nullable();
            $table->string('pdf_key')->nullable();
            $table->string('thumbnail_key')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('processing_status', 32)->default('pending');
            $table->text('processing_error')->nullable();
            $table->float('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('processing_status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdfs');
    }
};
