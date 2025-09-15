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
        $tableName = config('chat-soul.database.prefix') . 'conversation_user';
        $conversationsTable = config('chat-soul.database.prefix') . 'conversations';
        
        Schema::create($tableName, function (Blueprint $table) use ($conversationsTable) {
            $table->id();
            $table->foreignId('conversation_id')->constrained($conversationsTable)->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            
            // Constraints
            $table->unique(['conversation_id', 'user_id']);
            
            // Indexes
            $table->index('user_id');
            $table->index(['conversation_id', 'left_at']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('chat-soul.database.prefix') . 'conversation_user';
        Schema::dropIfExists($tableName);
    }
};