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
        $tableName = config('chat-soul.database.prefix') . 'messages';
        $conversationsTable = config('chat-soul.database.prefix') . 'conversations';
        
        Schema::create($tableName, function (Blueprint $table) use ($conversationsTable) {
            $table->id();
            $table->foreignId('conversation_id')->constrained($conversationsTable)->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->text('content')->nullable();
            $table->enum('type', ['text', 'file', 'image', 'system'])->default('text');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_type')->nullable();
            $table->unsignedInteger('attachment_size')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key for reply
            $table->foreign('reply_to_id')->references('id')->on($tableName)->onDelete('set null');
            
            // Indexes
            $table->index(['conversation_id', 'created_at']);
            $table->index('user_id');
            $table->index('type');
            $table->index('reply_to_id');
            $table->fullText(['content']); // For search functionality
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('chat-soul.database.prefix') . 'messages';
        Schema::dropIfExists($tableName);
    }
};