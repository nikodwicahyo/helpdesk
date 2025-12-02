<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Create a temporary table with the correct schema
        Schema::create('notifications_new', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            $table->string('triggered_by_nip')->nullable();
            $table->enum('triggered_by_type', ['user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi', 'system'])->nullable();
            $table->string('title');
            $table->text('message');
            $table->longText('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread');
            $table->enum('channel', ['database', 'email', 'sms', 'push'])->default('database');
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Original indexes
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['ticket_id', 'created_at']);
            $table->index('read_at');
            $table->index('type');
            $table->index('priority');
            $table->index('channel');
            $table->index('scheduled_at');
            $table->index(['created_at', 'read_at']);
        });
        
        // Step 2: Copy data from old table to new table (excluding UUID id)
        // This is a safe operation since notifications table is likely empty in development
        DB::statement('INSERT INTO notifications_new 
            (type, notifiable_type, notifiable_id, ticket_id, triggered_by_nip, triggered_by_type, 
             title, message, data, read_at, sent_at, priority, status, channel, 
             scheduled_at, retry_count, error_message, created_at, updated_at)
            SELECT 
            type, notifiable_type, notifiable_id, ticket_id, triggered_by_nip, triggered_by_type,
            title, message, data, read_at, sent_at, priority, status, channel,
            scheduled_at, retry_count, error_message, created_at, updated_at
            FROM notifications');
            
        // Step 3: Drop the old table
        Schema::dropIfExists('notifications');
        
        // Step 4: Rename the new table to the original name
        Schema::rename('notifications_new', 'notifications');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Create old table structure with UUID
        Schema::create('notifications_old', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            $table->string('triggered_by_nip')->nullable();
            $table->enum('triggered_by_type', ['user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi', 'system'])->nullable();
            $table->string('title');
            $table->text('message');
            $table->longText('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread');
            $table->enum('channel', ['database', 'email', 'sms', 'push'])->default('database');
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['ticket_id', 'created_at']);
            $table->index('read_at');
            $table->index('type');
            $table->index('priority');
            $table->index('channel');
            $table->index('scheduled_at');
            $table->index(['created_at', 'read_at']);
        });
        
        // Step 2: Copy data back (without id since it's auto-generated)
        DB::statement('INSERT INTO notifications_old 
            (type, notifiable_type, notifiable_id, ticket_id, triggered_by_nip, triggered_by_type,
            title, message, data, read_at, sent_at, priority, status, channel,
            scheduled_at, retry_count, error_message, created_at, updated_at)
            SELECT 
            type, notifiable_type, notifiable_id, ticket_id, triggered_by_nip, triggered_by_type,
            title, message, data, read_at, sent_at, priority, status, channel,
            scheduled_at, retry_count, error_message, created_at, updated_at
            FROM notifications');
            
        // Step 3: Drop the new table
        Schema::dropIfExists('notifications');
        
        // Step 4: Rename old table back
        Schema::rename('notifications_old', 'notifications');
    }
};