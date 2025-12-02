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
        Schema::table('user_sessions', function (Blueprint $table) {
            // Add composite index for performance optimization
            // This index helps queries that filter by nip, is_active, and last_activity together
            $table->index(['nip', 'is_active', 'last_activity'], 'idx_nip_active_activity');
            
            // Add index for session expiry cleanup queries
            $table->index(['expires_at', 'is_active'], 'idx_expires_active');
            
            // Add index for role-based queries
            $table->index(['user_role', 'is_active', 'last_activity'], 'idx_role_active_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            // Drop the indexes in reverse order
            $table->dropIndex('idx_role_active_activity');
            $table->dropIndex('idx_expires_active');
            $table->dropIndex('idx_nip_active_activity');
        });
    }
};
