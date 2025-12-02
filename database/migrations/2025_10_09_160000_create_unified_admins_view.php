<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a unified view for all admin types (both helpdesk and aplikasi admins)
        DB::statement("
            CREATE OR REPLACE VIEW unified_admins AS
            SELECT
                nip,
                name,
                email,
                'admin_helpdesk' as admin_type,
                department,
                position,
                status,
                permissions,
                created_at,
                updated_at
            FROM admin_helpdesks
            UNION ALL
            SELECT
                nip,
                name,
                email,
                'admin_aplikasi' as admin_type,
                department,
                position,
                status,
                permissions,
                created_at,
                updated_at
            FROM admin_aplikasis
        ");

        // Create indexes on the unified view for better performance
        DB::statement("CREATE INDEX idx_unified_admins_nip ON admin_helpdesks(nip)");
        DB::statement("CREATE INDEX idx_unified_admins_nip_aplikasi ON admin_aplikasis(nip)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS unified_admins");
    }
};
