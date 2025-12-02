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
        Schema::create('teknisi_knowledge_base_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->string('viewer_nip', 20);
            $table->string('viewer_type', 50)->default('teknisi');
            $table->timestamp('viewed_at')->useCurrent();
            
            $table->foreign('article_id')
                  ->references('id')
                  ->on('knowledge_base_articles')
                  ->onDelete('cascade');
            
            $table->index(['article_id', 'viewer_nip']);
            $table->index('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teknisi_knowledge_base_views');
    }
};
