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
        Schema::create('teknisis', function (Blueprint $table) {
            $table->string('nip')->primary(); // NIP as primary key for technical support staff
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('department')->nullable(); // Department/unit kerja
            $table->string('position')->nullable(); // Jabatan
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('skill_level', ['junior', 'senior', 'expert'])->default('junior');
            $table->json('skills')->nullable(); // Technical skills array
            $table->json('certifications')->nullable(); // Technical certifications
            $table->integer('ticket_count')->default(0); // Number of tickets handled
            $table->integer('current_ticket_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable(); // Average rating from users
            $table->integer('experience_years')->nullable(); // Years of experience
            $table->text('bio')->nullable(); // Short biography/description
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('max_concurrent_tickets')->default(10);
            $table->json('specializations')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->string('availability_status')->default('available');
            $table->decimal('workload_score', 5, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->integer('current_workload')->default(0);
            $table->rememberToken();
            $table->timestamps();


            // Indexes for performance
            $table->index(['department', 'status']);
            $table->index(['skill_level', 'status']);
            $table->index('email');
            $table->index('rating');
            $table->index('last_active_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teknisis');
    }
};