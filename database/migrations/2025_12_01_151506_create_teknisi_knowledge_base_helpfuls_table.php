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
        Schema::create('teknisi_knowledge_base_helpfuls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->string('voter_nip', 20);
            $table->string('voter_type', 50)->default('teknisi');
            $table->timestamp('voted_at')->useCurrent();
            
            $table->foreign('article_id')
                  ->references('id')
                  ->on('knowledge_base_articles')
                  ->onDelete('cascade');
            
            $table->unique(['article_id', 'voter_nip']);
            $table->index('voted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teknisi_knowledge_base_helpfuls');
    }
};
