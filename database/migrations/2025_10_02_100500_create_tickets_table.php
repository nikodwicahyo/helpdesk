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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // Auto-generated ticket number (e.g., TICKET-2025-001)
            $table->string('user_nip'); // Ticket creator (regular employee)
            $table->foreign('user_nip')->references('nip')->on('users')->onDelete('cascade');
            $table->foreignId('aplikasi_id')->constrained('aplikasis')->onDelete('cascade'); // Related application
            $table->foreignId('kategori_masalah_id')->constrained('kategori_masalahs')->onDelete('cascade'); // Problem category
            $table->string('assigned_teknisi_nip')->nullable(); // Assigned technician
            $table->foreign('assigned_teknisi_nip')->references('nip')->on('teknisis')->onDelete('set null');
            $table->string('assigned_by_nip')->nullable(); // Who assigned the ticket
            $table->foreign('assigned_by_nip')->references('nip')->on('admin_helpdesks')->onDelete('set null');

            // Ticket details
            $table->string('title'); // Brief title
            $table->longText('description'); // Detailed description
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', [
                'open',           // Newly created
                'assigned',       // Assigned to technician
                'in_progress',    // Being worked on
                'waiting_user',   // Waiting for user response
                'waiting_admin',  // Waiting for admin action
                'resolved',       // Fixed, waiting confirmation
                'closed',         // Confirmed closed
                'cancelled'       // Cancelled
            ])->default('open');

            // File attachments
            $table->json('attachments')->nullable(); // Array of attachment file paths
            $table->string('screenshot')->nullable(); // Screenshot file path

            // Location and device info
            $table->string('location')->nullable(); // User's location/department
            $table->string('device_info')->nullable(); // Device/browser info
            $table->string('ip_address')->nullable();

            // Resolution tracking
            $table->text('resolution_notes')->nullable(); // How it was resolved
            $table->integer('resolution_time_minutes')->nullable(); // Time taken to resolve
            $table->tinyInteger('user_rating')->nullable(); // 1-5 rating from user
            $table->text('user_feedback')->nullable(); // User's feedback

            // SLA and deadlines
            $table->timestamp('due_date')->nullable(); // When it should be resolved
            $table->timestamp('first_response_at')->nullable(); // When first response was given
            $table->timestamp('resolved_at')->nullable(); // When marked as resolved
            $table->timestamp('closed_at')->nullable(); // When finally closed

            // Additional metadata
            $table->json('metadata')->nullable(); // Additional custom fields
            $table->boolean('is_escalated')->default(false); // Whether escalated
            $table->string('escalation_reason')->nullable();
            $table->integer('view_count')->default(0); // How many times viewed

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_nip', 'status']);
            $table->index(['assigned_teknisi_nip', 'status']);
            $table->index(['aplikasi_id', 'status']);
            $table->index(['kategori_masalah_id', 'status']);
            $table->index(['priority', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('ticket_number');
            $table->index('due_date');
            $table->index('resolved_at');
            $table->index('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};