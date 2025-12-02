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
        // Add indexes for tickets table based on DashboardMetricsService queries
        Schema::table('tickets', function (Blueprint $table) {
            // Composite indexes for status-based queries
            $table->index(['status', 'priority'], 'tickets_status_priority_idx');
            $table->index(['status', 'created_at'], 'tickets_status_created_at_idx');
            $table->index(['status', 'updated_at'], 'tickets_status_updated_at_idx');

            // Indexes for date-based queries
            $table->index(['created_at', 'status'], 'tickets_created_at_status_idx');
            $table->index(['resolved_at', 'status'], 'tickets_resolved_at_status_idx');
            $table->index(['due_date', 'status'], 'tickets_due_date_status_idx');

            // Indexes for user-based queries
            $table->index(['user_nip', 'status'], 'tickets_user_nip_status_idx');
            $table->index(['assigned_teknisi_nip', 'status'], 'tickets_assigned_teknisi_status_idx');

            // Indexes for application and category queries
            $table->index(['aplikasi_id', 'status'], 'tickets_aplikasi_status_idx');
            $table->index(['kategori_masalah_id', 'status'], 'tickets_kategori_status_idx');

            // Index for user rating queries
            $table->index(['user_rating', 'status'], 'tickets_user_rating_status_idx');
        });

        // Add indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['status', 'department'], 'users_status_department_idx');
            $table->index(['department', 'created_at'], 'users_department_created_at_idx');
        });

        // Add indexes for teknisis table
        Schema::table('teknisis', function (Blueprint $table) {
            $table->index(['status', 'nip'], 'teknisis_status_nip_idx');
            $table->index(['nip', 'status'], 'teknisis_nip_status_idx');
        });

        // Add indexes for aplikasis table
        Schema::table('aplikasis', function (Blueprint $table) {
            $table->index(['status', 'criticality'], 'aplikasis_status_criticality_idx');
            $table->index(['status', 'category'], 'aplikasis_status_category_idx');
            $table->index(['category', 'status'], 'aplikasis_category_status_idx');
        });

        // Add indexes for kategori_masalahs table
        Schema::table('kategori_masalahs', function (Blueprint $table) {
            $table->index(['aplikasi_id', 'name'], 'kategori_aplikasi_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_status_priority_idx');
            $table->dropIndex('tickets_status_created_at_idx');
            $table->dropIndex('tickets_status_updated_at_idx');
            $table->dropIndex('tickets_created_at_status_idx');
            $table->dropIndex('tickets_resolved_at_status_idx');
            $table->dropIndex('tickets_due_date_status_idx');
            $table->dropIndex('tickets_user_nip_status_idx');
            $table->dropIndex('tickets_assigned_teknisi_status_idx');
            $table->dropIndex('tickets_aplikasi_status_idx');
            $table->dropIndex('tickets_kategori_status_idx');
            $table->dropIndex('tickets_user_rating_status_idx');
        });

        // Drop indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_department_idx');
            $table->dropIndex('users_department_created_at_idx');
        });

        // Drop indexes for teknisis table
        Schema::table('teknisis', function (Blueprint $table) {
            $table->dropIndex('teknisis_status_nip_idx');
            $table->dropIndex('teknisis_nip_status_idx');
        });

        // Drop indexes for aplikasis table
        Schema::table('aplikasis', function (Blueprint $table) {
            $table->dropIndex('aplikasis_status_criticality_idx');
            $table->dropIndex('aplikasis_status_category_idx');
            $table->dropIndex('aplikasis_category_status_idx');
        });

        // Drop indexes for kategori_masalahs table
        Schema::table('kategori_masalahs', function (Blueprint $table) {
            $table->dropIndex('kategori_aplikasi_name_idx');
        });
    }
};
