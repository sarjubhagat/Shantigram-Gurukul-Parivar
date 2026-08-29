<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('unique_key', 64)->unique();
            $table->uuid('upload_batch_id')->nullable();
            $table->string('title');
            $table->json('tags')->nullable();
            $table->string('alt_text', 500)->nullable();
            $table->string('image_key');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('processing_status', 32)->default('active');
            $table->text('processing_error')->nullable();
            $table->float('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('upload_batch_id');
            $table->index('processing_status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
