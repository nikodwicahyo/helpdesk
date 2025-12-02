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
        Schema::create('admin_helpdesks', function (Blueprint $table) {
            $table->string('nip')->primary(); // NIP as primary key for helpdesk coordinators
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('department')->nullable(); // Department/unit kerja
            $table->string('position')->nullable(); // Jabatan
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('permissions')->nullable(); // Additional permissions for helpdesk management
            $table->text('specialization')->nullable(); // Area of expertise
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Indexes for performance
            $table->index(['department', 'status']);
            $table->index('email');
            // Note: TEXT columns cannot be indexed directly in MySQL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_helpdesks');
    }
};