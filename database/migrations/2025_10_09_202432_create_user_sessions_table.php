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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 255)->unique();
            $table->string('nip', 18)->index();
            $table->string('user_role', 50)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('session_data')->nullable();
            $table->timestamp('login_time');
            $table->timestamp('last_activity');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->string('device_info', 255)->nullable();
            $table->string('location_info', 255)->nullable();

            // Indexes for performance
            $table->index(['nip', 'is_active']);
            $table->index(['user_role', 'is_active']);
            $table->index(['last_activity']);
            $table->index(['expires_at']);

            $table->timestamps();

            // Foreign key constraint (optional, depending on requirements)
            // $table->foreign('nip')->references('nip')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
