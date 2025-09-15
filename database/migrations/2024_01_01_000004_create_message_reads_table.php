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
        $tableName = config('chat-soul.database.prefix') . 'message_reads';
        $messagesTable = config('chat-soul.database.prefix') . 'messages';
        
        Schema::create($tableName, function (Blueprint $table) use ($messagesTable) {
            $table->id();
            $table->foreignId('message_id')->constrained($messagesTable)->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at');
            $table->timestamps();
            
            // Constraints
            $table->unique(['message_id', 'user_id']);
            
            // Indexes
            $table->index('user_id');
            $table->index(['message_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('chat-soul.database.prefix') . 'message_reads';
        Schema::dropIfExists($tableName);
    }
};