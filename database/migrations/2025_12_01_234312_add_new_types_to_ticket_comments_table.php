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
        // Modify enum to include new types: general, technical, reassignment_request, solution_doc
        DB::statement("ALTER TABLE ticket_comments MODIFY COLUMN type ENUM('comment', 'general', 'status_update', 'assignment', 'resolution', 'escalation', 'technical', 'reassignment_request', 'solution_doc') DEFAULT 'comment'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE ticket_comments MODIFY COLUMN type ENUM('comment', 'status_update', 'assignment', 'resolution', 'escalation') DEFAULT 'comment'");
    }
};
