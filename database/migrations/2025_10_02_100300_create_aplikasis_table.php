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
        Schema::create('aplikasis', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Application name
            $table->string('code')->unique(); // Application code/acronym
            $table->text('description')->nullable(); // Application description
            $table->string('version')->nullable(); // Current version
            $table->enum('status', ['active', 'inactive', 'maintenance', 'deprecated'])->default('active');
            $table->enum('criticality', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('category')->nullable(); // Application category (e.g., HR, Finance, etc.)
            $table->string('vendor')->nullable(); // Software vendor
            $table->string('contact_person')->nullable(); // Technical contact person
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('technical_documentation')->nullable(); // Link to documentation
            $table->json('supported_os')->nullable(); // Supported operating systems
            $table->json('supported_browsers')->nullable(); // Supported browsers
            $table->string('server_location')->nullable(); // Server location/IP
            $table->string('backup_schedule')->nullable(); // Backup schedule info
            $table->text('notes')->nullable(); // Additional notes
            $table->string('icon')->nullable(); // Application icon path
            $table->integer('sort_order')->default(0); // For ordering applications
            $table->decimal('uptime_percentage', 5, 2)->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->enum('health_status', ['healthy', 'warning', 'critical'])->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'criticality']);
            $table->index('category');
            $table->index('code');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplikasis');
    }
};