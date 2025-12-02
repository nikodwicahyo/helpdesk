<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->string('author_nip', 20);
            $table->string('title', 200);
            $table->text('content');
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('kategori_masalah_id')->nullable();
            $table->unsignedBigInteger('aplikasi_id')->nullable();
            $table->json('tags')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('view_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('author_nip')->references('nip')->on('teknisis')->onDelete('cascade');
            $table->foreign('kategori_masalah_id')->references('id')->on('kategori_masalahs')->onDelete('set null');
            $table->foreign('aplikasi_id')->references('id')->on('aplikasis')->onDelete('set null');
            
            // Indexes
            $table->index('status');
            $table->index('author_nip');
            $table->index(['aplikasi_id', 'status']);
            $table->index('created_at');
            $table->fullText(['title', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_articles');
    }
};
