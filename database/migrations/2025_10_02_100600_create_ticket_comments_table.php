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
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->string('commenter_nip'); // NIP of who made the comment (can be from any user type)
            $table->string('commenter_type'); // Polymorphic type (full class name)
            $table->longText('comment'); // The comment content
            $table->enum('type', ['comment', 'status_update', 'assignment', 'resolution', 'escalation'])->default('comment');
            $table->json('attachments')->nullable(); // File attachments for the comment
            $table->boolean('is_internal')->default(false); // Internal notes not visible to users
            $table->boolean('requires_response')->default(false); // Whether this comment requires a response
            $table->timestamp('responded_at')->nullable(); // When response was provided
            $table->text('technical_details')->nullable(); // Technical details for internal use
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();

            // Indexes for performance
            $table->index(['ticket_id', 'created_at']);
            $table->index(['commenter_nip', 'commenter_type']);
            $table->index(['type', 'is_internal']);
            $table->index('requires_response');
            $table->index('responded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};