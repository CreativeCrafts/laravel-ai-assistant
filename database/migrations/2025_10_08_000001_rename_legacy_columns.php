<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // Helper to perform a safe column rename across common drivers
        $rename = function (string $table, string $from, string $to): void {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $from)) {
                return; // nothing to do
            }

            $driver = Schema::getConnection()->getDriverName();

            // Prefer SQL standard syntax supported by modern MySQL/MariaDB, PostgreSQL, SQLite
            $sql = match ($driver) {
                'mysql', 'mariadb' => "ALTER TABLE `{$table}` RENAME COLUMN `{$from}` TO `{$to}`",
                'pgsql' => "ALTER TABLE \"{$table}\" RENAME COLUMN \"{$from}\" TO \"{$to}\"",
                'sqlite' => "ALTER TABLE \"{$table}\" RENAME COLUMN \"{$from}\" TO \"{$to}\"",
                default => null,
            };

            if ($sql !== null) {
                DB::statement($sql);
            }
        };

        // assistant_id → conversation_id on potential legacy tables
        $rename('ai_conversation_items', 'assistant_id', 'conversation_id');
        $rename('ai_responses', 'assistant_id', 'conversation_id');
        $rename('ai_conversations', 'assistant_id', 'conversation_id');

        // thread_id → conversation_id (legacy thread mapping)
        $rename('ai_conversation_items', 'thread_id', 'conversation_id');
        $rename('ai_responses', 'thread_id', 'conversation_id');

        // run_id → response_id
        $rename('ai_tool_invocations', 'run_id', 'response_id');
        $rename('ai_conversation_items', 'run_id', 'response_id');
    }

    public function down(): void
    {
        // Best-effort reverse renames (won't run if columns do not exist)
        $rename = function (string $table, string $from, string $to): void {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $from)) {
                return;
            }

            $driver = Schema::getConnection()->getDriverName();
            $sql = match ($driver) {
                'mysql', 'mariadb' => "ALTER TABLE `{$table}` RENAME COLUMN `{$from}` TO `{$to}`",
                'pgsql' => "ALTER TABLE \"{$table}\" RENAME COLUMN \"{$from}\" TO \"{$to}\"",
                'sqlite' => "ALTER TABLE \"{$table}\" RENAME COLUMN \"{$from}\" TO \"{$to}\"",
                default => null,
            };
            if ($sql !== null) {
                DB::statement($sql);
            }
        };

        // Reverse (unlikely to be used)
        $rename('ai_conversation_items', 'conversation_id', 'thread_id');
        $rename('ai_responses', 'conversation_id', 'thread_id');
        $rename('ai_conversations', 'conversation_id', 'assistant_id');
        $rename('ai_tool_invocations', 'response_id', 'run_id');
        $rename('ai_conversation_items', 'response_id', 'run_id');
    }
};
