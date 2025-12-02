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
        // Populate role field for users table
        DB::table('users')->update(['role' => 'user']);

        // Populate role field for admin_helpdesks table
        DB::table('admin_helpdesks')->update(['role' => 'admin_helpdesk']);

        // Populate role field for admin_aplikasis table
        DB::table('admin_aplikasis')->update(['role' => 'admin_aplikasi']);

        // Populate role field for teknisis table
        DB::table('teknisis')->update(['role' => 'teknisi']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Since this is a data migration, we cannot reverse the data changes
        // The role columns will be dropped by the previous migration's down() method
        // This method is here for completeness but cannot restore original data
    }
};
