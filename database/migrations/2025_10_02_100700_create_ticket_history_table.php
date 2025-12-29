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
        Schema::create('ticket_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->string('action'); // Action performed (e.g., 'created', 'assigned', 'status_changed', 'commented')
            $table->string('performed_by_nip')->nullable(); // NIP of who performed the action
            $table->enum('performed_by_type', ['user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi', 'system']); // Type of performer
            $table->string('field_name')->nullable(); // Which field was changed
            $table->text('old_value')->nullable(); // Previous value
            $table->text('new_value')->nullable(); // New value
            $table->text('description')->nullable(); // Human-readable description of the change
            $table->json('metadata')->nullable(); // Additional context/metadata
            $table->string('ip_address')->nullable(); // IP address of the performer
            $table->string('user_agent')->nullable(); // Browser/client info
            $table->timestamps();

            // Indexes for performance
            $table->index(['ticket_id', 'created_at']);
            $table->index(['performed_by_nip', 'performed_by_type']);
            $table->index(['action', 'created_at']);
            $table->index('field_name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_history');
    }
};