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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');
            $table->string('category')->default('general')->index();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('updated_by_nip')->nullable();
            $table->string('updated_by_type')->nullable(); // Model type: admin_helpdesk, admin_aplikasi, etc.
            $table->timestamps();

            // Indexes for performance
            $table->index('key');
            $table->index('updated_at');
            $table->index(['updated_by_nip', 'updated_by_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
