<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Console\Commands;

use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use Illuminate\Console\Command;
use JsonException;

final class AiCacheStatsCommand extends Command
{
    protected $signature = 'ai-cache:stats';
    protected $description = 'Show AI Assistant cache statistics';

    /**
     * @throws JsonException
     */
    public function handle(CacheService $cache): int
    {
        $stats = $cache->getStats();
        $json = json_encode($stats, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->line($json);
        return self::SUCCESS;
    }
}
