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
        Schema::create('ticket_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('actor_nip', 20);
            $table->string('actor_type', 50); // 'user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi'
            $table->string('action_type', 50); // 'created', 'assigned', 'status_changed', 'commented', 'resolved', 'closed', etc.
            $table->text('description');
            $table->json('metadata')->nullable(); // Additional data about the action
            $table->timestamps();
            
            // Foreign key
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            
            // Indexes
            $table->index('ticket_id');
            $table->index('actor_nip');
            $table->index('action_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_actions');
    }
};
