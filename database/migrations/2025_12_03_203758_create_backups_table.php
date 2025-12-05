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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('type')->default('manual'); // manual, daily, weekly, monthly
            $table->bigInteger('size')->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, completed, failed
            $table->string('location')->default('local'); // local, s3, google_drive
            $table->string('path')->nullable();
            $table->string('disk')->default('local');
            $table->text('notes')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('include_files')->default(true);
            $table->string('created_by_nip')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['created_by_nip', 'created_by_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
