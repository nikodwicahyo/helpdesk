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
        Schema::create('teknisi_aplikasi_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('teknisi_nip');
            $table->unsignedBigInteger('aplikasi_id');
            $table->string('assigned_by_nip')->nullable(); // Who assigned this teknisi
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('teknisi_nip')->references('nip')->on('teknisis')->onDelete('cascade');
            $table->foreign('aplikasi_id')->references('id')->on('aplikasis')->onDelete('cascade');
            $table->foreign('assigned_by_nip')->references('nip')->on('admin_helpdesks')->onDelete('set null');

            // Unique constraint to prevent duplicate assignments
            $table->unique(['teknisi_nip', 'aplikasi_id']);

            // Indexes
            $table->index(['teknisi_nip', 'aplikasi_id']);
            $table->index('assigned_by_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teknisi_aplikasi_assignments');
    }
};
