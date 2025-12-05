<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes the foreign key constraint on assigned_by_nip column.
     * The original constraint only allowed NIPs from admin_helpdesks table,
     * but admin_aplikasi users also need to assign teknisi using their own NIP.
     * 
     * Solution: Remove the foreign key constraint since assigned_by_nip can be
     * from either admin_helpdesks OR admin_aplikasis table.
     */
    public function up(): void
    {
        Schema::table('teknisi_aplikasi_assignments', function (Blueprint $table) {
            // Drop the foreign key constraint that only references admin_helpdesks
            // The constraint name follows Laravel convention: {table}_{column}_foreign
            $table->dropForeign(['assigned_by_nip']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teknisi_aplikasi_assignments', function (Blueprint $table) {
            // Re-add the foreign key constraint (only if needed to rollback)
            $table->foreign('assigned_by_nip')
                  ->references('nip')
                  ->on('admin_helpdesks')
                  ->onDelete('set null');
        });
    }
};
