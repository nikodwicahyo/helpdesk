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
        Schema::create('ticket_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('user_nip', 20);
            $table->unsignedBigInteger('aplikasi_id')->nullable();
            $table->unsignedBigInteger('kategori_masalah_id')->nullable();
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('location', 100)->nullable();
            $table->json('draft_data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_nip');
            $table->index('expires_at');
            $table->unique('user_nip');

            // Foreign keys
            $table->foreign('user_nip')->references('nip')->on('users')->onDelete('cascade');
            $table->foreign('aplikasi_id')->references('id')->on('aplikasis')->onDelete('set null');
            $table->foreign('kategori_masalah_id')->references('id')->on('kategori_masalahs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_drafts');
    }
};
