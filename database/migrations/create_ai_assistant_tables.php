<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ai_assistant_profiles', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->string('default_model')->nullable();
            $table->text('default_instructions')->nullable();
            $table->json('tools')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
        });

        Schema::create('ai_conversation_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('conversation_id');
            $table->string('role')->nullable();
            $table->json('content')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index('conversation_id');
        });

        Schema::create('ai_responses', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('conversation_id');
            $table->string('status')->nullable();
            $table->text('output_summary')->nullable();
            $table->json('token_usage')->nullable();
            $table->json('timings')->nullable();
            $table->json('error')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('conversation_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['conversation_id', 'status']);
        });

        Schema::create('ai_tool_invocations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('response_id');
            $table->string('name');
            $table->json('arguments')->nullable();
            $table->string('state')->nullable();
            $table->json('result_summary')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('response_id');
            $table->index('name');
            $table->index('state');
            $table->index('created_at');
            $table->index(['response_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_invocations');
        Schema::dropIfExists('ai_responses');
        Schema::dropIfExists('ai_conversation_items');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_assistant_profiles');
    }
};
