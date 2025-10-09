<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

it('migrates legacy assistant/thread/run columns to new schema', function () {
    // Create legacy-shaped tables with assistant_id/thread_id/run_id
    Schema::create('ai_conversations', function ($table) {
        $table->string('id')->primary();
        $table->string('assistant_id')->nullable(); // legacy
        $table->timestamps();
    });

    Schema::create('ai_conversation_items', function ($table) {
        $table->string('id')->primary();
        $table->string('thread_id'); // legacy
        $table->string('run_id')->nullable(); // legacy
        $table->json('content')->nullable();
        $table->timestamps();
    });

    Schema::create('ai_responses', function ($table) {
        $table->string('id')->primary();
        $table->string('thread_id')->nullable(); // legacy
        $table->string('assistant_id')->nullable(); // legacy
        $table->string('status')->nullable();
        $table->timestamps();
    });

    Schema::create('ai_tool_invocations', function ($table) {
        $table->string('id')->primary();
        $table->string('run_id'); // legacy
        $table->string('name');
        $table->timestamps();
    });

    // Seed a bit of legacy data
    DB::table('ai_conversations')->insert(['id' => 'conv_1', 'assistant_id' => 'asst_1']);
    DB::table('ai_conversation_items')->insert(['id' => 'itm_1', 'thread_id' => 'conv_1', 'run_id' => 'run_1', 'content' => json_encode([['text' => 'hi']])]);
    DB::table('ai_responses')->insert(['id' => 'resp_1', 'thread_id' => 'conv_1', 'assistant_id' => 'asst_1', 'status' => 'completed']);
    DB::table('ai_tool_invocations')->insert(['id' => 'tool_1', 'run_id' => 'run_1', 'name' => 'sum']);

    // Run the rename migration
    $migrationPath = realpath(__DIR__ . '/../../database/migrations/2025_10_08_000001_rename_legacy_columns.php');
    expect(is_string($migrationPath) && file_exists($migrationPath))->toBeTrue();
    $migration = require $migrationPath;
    $migration->up();

    // Assert new columns exist and old ones gone
    expect(Schema::hasColumn('ai_conversations', 'conversation_id'))->toBeTrue();
    expect(Schema::hasColumn('ai_conversations', 'assistant_id'))->toBeFalse();

    expect(Schema::hasColumn('ai_conversation_items', 'conversation_id'))->toBeTrue();
    expect(Schema::hasColumn('ai_conversation_items', 'thread_id'))->toBeFalse();
    expect(Schema::hasColumn('ai_conversation_items', 'response_id'))->toBeTrue();
    expect(Schema::hasColumn('ai_conversation_items', 'run_id'))->toBeFalse();

    expect(Schema::hasColumn('ai_responses', 'conversation_id'))->toBeTrue();
    expect(Schema::hasColumn('ai_responses', 'thread_id'))->toBeFalse();

    expect(Schema::hasColumn('ai_tool_invocations', 'response_id'))->toBeTrue();
    expect(Schema::hasColumn('ai_tool_invocations', 'run_id'))->toBeFalse();

    // Validate data preserved under new columns
    $itm = DB::table('ai_conversation_items')->where('id', 'itm_1')->first();
    expect($itm)->not->toBeNull();
    // conversation_id should equal prior thread_id
    expect((string)($itm->conversation_id ?? ''))->toBe('conv_1');

    $tool = DB::table('ai_tool_invocations')->where('id', 'tool_1')->first();
    expect((string)($tool->response_id ?? ''))->toBe('run_1');
});
