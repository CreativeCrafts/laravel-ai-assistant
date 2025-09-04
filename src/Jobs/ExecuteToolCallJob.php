<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Jobs;

use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ExecuteToolCallJob dispatches a named tool invocation via the ToolRegistry.
 * It is used by the queue-backed executor to simulate parallel tool execution.
 */
class ExecuteToolCallJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param string $toolName
     * @param array $arguments
     */
    public function __construct(
        public string $toolName,
        public array $arguments = []
    ) {
    }

    /**
     * Handle the job and return the tool's output. When dispatched synchronously
     * via Bus::dispatchSync(), the return value will be available to the caller.
     */
    public function handle(): mixed
    {
        /** @var ToolRegistry $registry */
        $registry = app(ToolRegistry::class);
        return $registry->call($this->toolName, $this->arguments);
    }
}
