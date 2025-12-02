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
        Schema::table('sessions', function (Blueprint $table) {
            // Drop the existing index first if it exists
            $table->dropIndex(['user_id']);
            
            // Change user_id from bigint unsigned to varchar to accommodate NIP values
            $table->string('user_id', 18)->nullable()->change();
            
            // Recreate the index
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['user_id']);
            
            // Change back to bigint unsigned
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Recreate the index
            $table->index(['user_id']);
        });
    }
};
