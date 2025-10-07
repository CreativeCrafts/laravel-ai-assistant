<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Console\Commands;

use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class AiCacheClearCommand extends Command
{
    protected $signature = 'ai-cache:clear {--area=} {--key=} {--prefix=}';
    protected $description = 'Clear AI Assistant cache safely by area, key, or prefix';

    public function handle(CacheService $cache): int
    {
        $area = (string) ($this->option('area') ?? '');
        $key = $this->option('key');
        $prefix = $this->option('prefix');

        if ($key !== null) {
            if ($area === 'config') {
                $ok = $cache->deleteConfig((string) $key);
                $this->info($ok ? 'Deleted config key.' : 'Config key not found.');
                return self::SUCCESS;
            }
            if ($area === 'response') {
                $ok = $cache->deleteResponse((string) $key);
                $this->info($ok ? 'Deleted response key.' : 'Response key not found.');
                return self::SUCCESS;
            }
            $this->error('Deleting by --key requires --area=config|response. Completions use hashed keys; use --prefix=completion: instead.');
            return self::INVALID;
        }

        if (is_string($prefix) && $prefix !== '') {
            if (!in_array($prefix, ['config:', 'response:', 'completion:'], true)) {
                $this->error('Invalid --prefix. Use one of: config:, response:, completion:');
                return self::INVALID;
            }
            $deleted = $cache->purgeByPrefix($prefix);
            $this->info("Purged prefix {$prefix}. Deleted (known): {$deleted}");
            return self::SUCCESS;
        }

        // If area only
        if ($area === 'config') {
            $ok = $cache->clearConfig();
            $this->info($ok ? 'Cleared config cache.' : 'Failed to clear config cache.');
            return $ok ? self::SUCCESS : self::FAILURE;
        }
        if ($area === 'response') {
            $ok = $cache->clearResponses();
            $this->info($ok ? 'Cleared response cache.' : 'Failed to clear response cache.');
            return $ok ? self::SUCCESS : self::FAILURE;
        }
        if ($area === 'completion') {
            $ok = $cache->clearCompletions();
            $this->info($ok ? 'Cleared completion cache.' : 'Failed to clear completion cache.');
            return $ok ? self::SUCCESS : self::FAILURE;
        }

        // Default: clear all logically (uses tags or indexer)
        try {
            $ok = $cache->clearAll();
            $this->info($ok ? 'Cleared all AI Assistant cache areas.' : 'Failed to clear all AI Assistant cache areas.');
            return $ok ? self::SUCCESS : self::FAILURE;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::INVALID;
        }
    }
}
