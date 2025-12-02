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
        Schema::create('kategori_masalahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aplikasi_id')->constrained('aplikasis')->onDelete('cascade'); // Reference to applications
            $table->string('name'); // Category name (e.g., "Login Issues", "Performance")
            $table->text('description')->nullable(); // Category description
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0); // For ordering categories
            $table->string('icon')->nullable(); // Category icon
            $table->json('keywords')->nullable(); // Search keywords for auto-categorization
            $table->integer('estimated_resolution_time')->nullable(); // Estimated time in minutes
            $table->text('common_solutions')->nullable(); // Common solutions for technicians
            $table->boolean('requires_attachment')->default(false); // Whether tickets need attachments
            $table->unsignedBigInteger('parent_id')->nullable(); // For category hierarchy
            $table->integer('level')->default(0); // Hierarchy level (0 = root, 1 = child, etc.)
            $table->string('color')->nullable(); // Category color for UI
            $table->decimal('sla_hours', 8, 2)->nullable(); // SLA hours for this category
            $table->integer('ticket_count')->default(0); // Total tickets in this category
            $table->integer('resolved_count')->default(0); // Resolved tickets count
            $table->decimal('avg_resolution_time', 10, 2)->nullable(); // Average resolution time in minutes
            $table->decimal('success_rate', 5, 2)->default(0); // Success rate percentage
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();

            // Foreign key constraint for parent_id
            $table->foreign('parent_id')->references('id')->on('kategori_masalahs')->onDelete('cascade');

            // Indexes for performance
            $table->index(['aplikasi_id', 'status']);
            $table->index(['priority', 'status']);
            $table->index('name');
            $table->index('sort_order');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_masalahs');
    }
};