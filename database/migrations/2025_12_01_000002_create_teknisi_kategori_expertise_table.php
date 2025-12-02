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
        Schema::create('teknisi_kategori_expertise', function (Blueprint $table) {
            $table->id();
            $table->string('teknisi_nip');
            $table->foreignId('kategori_masalah_id')->constrained('kategori_masalahs')->onDelete('cascade');
            $table->enum('expertise_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->decimal('success_rate', 5, 2)->nullable()->default(0);
            $table->integer('avg_resolution_time')->nullable()->default(0)->comment('Average resolution time in minutes');
            $table->timestamps();

            $table->foreign('teknisi_nip')->references('nip')->on('teknisis')->onDelete('cascade');
            $table->unique(['teknisi_nip', 'kategori_masalah_id'], 'teknisi_kategori_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teknisi_kategori_expertise');
    }
};
