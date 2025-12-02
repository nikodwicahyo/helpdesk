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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Use UUID for notifications
            $table->string('type'); // Notification type (e.g., 'ticket_assigned', 'ticket_updated')
            $table->string('notifiable_type'); // Polymorphic relationship (users, teknisis, admin_helpdesks, admin_aplikasis)
            $table->string('notifiable_id'); // Changed to string to support NIP-based primary keys
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            $table->string('triggered_by_nip')->nullable(); // NIP of who triggered the notification
            $table->enum('triggered_by_type', ['user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi', 'system'])->nullable();

            // Notification content
            $table->string('title'); // Notification title
            $table->text('message'); // Notification message
            $table->longText('data')->nullable(); // Additional data (JSON)

            // Notification status
            $table->timestamp('read_at')->nullable(); // When notification was read
            $table->timestamp('sent_at')->nullable(); // When notification was sent
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread');
            $table->enum('channel', ['database', 'email', 'sms', 'push'])->default('database');

            // Scheduling and delivery
            $table->timestamp('scheduled_at')->nullable(); // When to send the notification
            $table->integer('retry_count')->default(0); // Number of retry attempts
            $table->text('error_message')->nullable(); // Last error message if failed

            $table->timestamps();

            // Indexes for performance
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['ticket_id', 'created_at']);
            $table->index('read_at');
            $table->index('type');
            $table->index('priority');
            $table->index('channel');
            $table->index('scheduled_at');
            $table->index(['created_at', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};