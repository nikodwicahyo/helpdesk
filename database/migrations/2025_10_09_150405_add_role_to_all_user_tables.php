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
        // Add role column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin_helpdesk', 'admin_aplikasi', 'teknisi'])->default('user');
        });

        // Add role column to admin_helpdesks table
        Schema::table('admin_helpdesks', function (Blueprint $table) {
            $table->enum('role', ['admin_helpdesk'])->default('admin_helpdesk');
        });

        // Add role column to admin_aplikasis table
        Schema::table('admin_aplikasis', function (Blueprint $table) {
            $table->enum('role', ['admin_aplikasi'])->default('admin_aplikasi');
        });

        // Add role column to teknisis table
        Schema::table('teknisis', function (Blueprint $table) {
            $table->enum('role', ['teknisi'])->default('teknisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop role column from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Drop role column from admin_helpdesks table
        Schema::table('admin_helpdesks', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Drop role column from admin_aplikasis table
        Schema::table('admin_aplikasis', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Drop role column from teknisis table
        Schema::table('teknisis', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
