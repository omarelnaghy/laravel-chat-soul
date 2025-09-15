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
        $tableName = config('chat-soul.database.prefix') . 'conversations';
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->boolean('is_private')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['type', 'created_at']);
            $table->index('created_by');
            $table->index(['is_private', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('chat-soul.database.prefix') . 'conversations';
        Schema::dropIfExists($tableName);
    }
};