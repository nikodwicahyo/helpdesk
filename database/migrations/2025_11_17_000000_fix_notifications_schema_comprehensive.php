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
        Schema::table('notifications', function (Blueprint $table) {
            // Add missing is_read column for backward compatibility
            if (!Schema::hasColumn('notifications', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('read_at');
            }

            // Add additional columns as required by the NotificationService
            if (!Schema::hasColumn('notifications', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }

            if (!Schema::hasColumn('notifications', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('delivered_at');
            }

            if (!Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('expires_at');
            }

            if (!Schema::hasColumn('notifications', 'icon')) {
                $table->string('icon')->nullable()->after('action_url');
            }

            if (!Schema::hasColumn('notifications', 'group_id')) {
                $table->integer('group_id')->nullable()->after('icon');
            }

            if (!Schema::hasColumn('notifications', 'metadata')) {
                $table->longText('metadata')->nullable()->after('group_id');
            }

            // Modify status enum to include all required values
            $table->enum('status', [
                'unread', 'read', 'archived', 'pending', 'sent', 'delivered', 'failed'
            ])->default('unread')->change();
            
            // Ensure data column is longText for JSON storage
            $table->longText('data')->change();
        });

        // Add indexes for improved performance
        Schema::table('notifications', function (Blueprint $table) {
            // Index on is_read for quick filtering
            $table->index('is_read');
            
            // Composite index for common queries
            $table->index(['notifiable_type', 'notifiable_id', 'is_read']);
            
            // Index on status for filtering
            $table->index('status');
            
            // Index on group_id for grouping functionality
            $table->index('group_id');
            
            // Index on ticket_id if not already present
            $table->index('ticket_id');
        });

        // Data migration: Set is_read based on read_at
        DB::statement("
            UPDATE notifications 
            SET is_read = CASE 
                WHEN read_at IS NOT NULL THEN true 
                ELSE false 
            END
        ");
        
        // Update status based on read_at
        DB::statement("
            UPDATE notifications 
            SET status = CASE 
                WHEN read_at IS NOT NULL AND status = 'unread' THEN 'read' 
                WHEN read_at IS NULL AND status = 'read' THEN 'unread' 
                ELSE status 
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['is_read']);
            $table->dropIndex(['notifiable_type', 'notifiable_id', 'is_read']);
            $table->dropIndex(['status']);
            $table->dropIndex(['group_id']);
            $table->dropIndex(['ticket_id']);

            $table->dropColumn([
                'is_read',
                'delivered_at', 
                'expires_at',
                'action_url',
                'icon',
                'group_id',
                'metadata'
            ]);

            // Revert status enum to original values
            $table->enum('status', ['unread', 'read', 'archived'])->change();
            
            // Revert data column to text if it was changed
            $table->text('data')->change();
        });
    }
};