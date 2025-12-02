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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('report_type'); // tickets, performance, user_activity, sla, application, summary
            $table->json('parameters'); // Report parameters like date range, filters, include options
            $table->json('filters'); // Applied filters
            $table->string('schedule_frequency'); // daily, weekly, monthly
            $table->time('schedule_time'); // Time of day to run
            $table->json('recipients'); // Array of email addresses
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('description')->nullable();
            $table->string('created_by')->nullable();
            // We'll handle the foreign key manually in a separate migration
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'next_run_at']);
            $table->index(['report_type', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
