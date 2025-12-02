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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // Type of report (e.g., 'daily_tickets', 'monthly_performance', 'user_satisfaction')
            $table->string('title'); // Report title
            $table->text('description')->nullable(); // Report description
            $table->date('report_date'); // Date the report covers
            $table->string('period_type'); // 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'
            $table->date('period_start'); // Start of reporting period
            $table->date('period_end'); // End of reporting period
            $table->string('generated_by_nip')->nullable(); // NIP of who generated the report
            $table->enum('generated_by_type', ['admin_helpdesk', 'admin_aplikasi', 'system'])->nullable();

            // Report data and configuration
            $table->json('data'); // The actual report data
            $table->json('filters')->nullable(); // Filters applied to generate the report
            $table->json('parameters')->nullable(); // Report parameters
            $table->longText('sql_query')->nullable(); // SQL query used (for audit purposes)

            // Report metadata
            $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
            $table->integer('record_count')->default(0); // Number of records in the report
            $table->decimal('execution_time_seconds', 8, 3)->nullable(); // How long it took to generate
            $table->text('error_message')->nullable(); // Error message if failed
            $table->string('file_path')->nullable(); // Path to generated file (PDF, Excel, etc.)
            $table->enum('file_format', ['json', 'csv', 'pdf', 'excel'])->nullable();

            // Scheduling and automation
            $table->boolean('is_scheduled')->default(false); // Whether this is a scheduled report
            $table->string('schedule_frequency')->nullable(); // 'daily', 'weekly', 'monthly'
            $table->time('schedule_time')->nullable(); // Time to run scheduled report
            $table->json('recipients')->nullable(); // Email recipients for scheduled reports

            // Access control
            $table->enum('visibility', ['public', 'internal', 'private'])->default('internal');
            $table->json('allowed_roles')->nullable(); // Which roles can access this report

            $table->timestamps();

            // Indexes for performance
            $table->index(['report_type', 'report_date']);
            $table->index(['period_type', 'period_start', 'period_end']);
            $table->index(['generated_by_nip', 'generated_by_type']);
            $table->index(['status', 'created_at']);
            $table->index('report_date');
            $table->index('is_scheduled');
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};