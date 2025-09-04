<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use InvalidArgumentException;
use Throwable;

/**
 * ToolRegistry allows registering PHP callables as tools by name, with optional JSON schema metadata.
 * It also supports an optional executor callable to run tools asynchronously (e.g., queue-backed).
 */
final class ToolRegistry
{
    /** @var array<string, callable> */
    private array $tools = [];

    /** @var array<string, array> */
    private array $schemas = [];

    /** @var null|callable */
    private $executor = null;

    /**
     * Register a tool.
     *
     * @param string $name
     * @param callable $callable function(array $args): mixed
     * @param array $schema JSON-schema-like descriptor for Responses.tools (type=function)
     */
    public function register(string $name, callable $callable, array $schema = []): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Tool name cannot be empty.');
        }
        $this->tools[$name] = $callable;
        if ($schema !== []) {
            $this->schemas[$name] = $schema;
        }
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->tools);
    }

    /**
     * Execute a tool by name with decoded arguments.
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function call(string $name, array $args = []): mixed
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException("Tool '{$name}' is not registered.");
        }
        $fn = $this->tools[$name];
        return $fn($args);
    }

    /**
     * Get the schema of a registered tool if provided.
     *
     * @param string $name
     * @return array|null
     */
    public function getSchema(string $name): ?array
    {
        return $this->schemas[$name] ?? null;
    }

    /**
     * Set an executor callable to dispatch tool executions.
     * Signature: function(callable $fn, array $args): mixed
     * Provide an executor that dispatches to queues for parallelism if desired.
     */
    public function setExecutor(callable $executor): void
    {
        $this->executor = $executor;
    }

    /**
     * Execute a batch of tool calls.
     * @param array<int, array{name:string,args:array,tool_call_id?:string}> $calls
     * @param bool $parallel If true, the provided executor may run tasks concurrently. Default sync when no executor.
     * @return array<int, array{name:string, tool_call_id?:string, output:mixed}>
     */
    public function executeAll(array $calls, bool $parallel = false): array
    {
        $results = [];
        if ($this->executor !== null) {
            foreach ($calls as $call) {
                $name = $call['name'];
                $args = $call['args'];
                $toolCallId = $call['tool_call_id'] ?? null;
                // Pass along name and parallel flag for executors
                if (!is_array($args)) {
                    $args = [];
                }
                $args['__name'] = $name;
                $args['__parallel'] = (bool) $parallel;
                try {
                    $output = ($this->executor)(function (array $args) use ($name) {
                        // Remove internal helper keys if present
                        if (isset($args['__name'])) {
                            unset($args['__name']);
                        }
                        if (isset($args['__parallel'])) {
                            unset($args['__parallel']);
                        }
                        return $this->call($name, $args);
                    }, $args);
                } catch (Throwable $e) {
                    $output = ['error' => $e->getMessage()];
                }
                $result = ['name' => $name, 'output' => $output];
                if (is_string($toolCallId) && $toolCallId !== '') {
                    $result['tool_call_id'] = $toolCallId;
                }
                $results[] = $result;
            }
            return $results;
        }
        // Default sync execution
        foreach ($calls as $call) {
            $name = $call['name'];
            $args = $call['args'];
            $toolCallId = $call['tool_call_id'] ?? null;
            try {
                if (!is_array($args)) {
                    $args = [];
                }
                $output = $this->call($name, $args);
            } catch (Throwable $e) {
                $output = ['error' => $e->getMessage()];
            }
            $result = ['name' => $name, 'output' => $output];
            if (is_string($toolCallId) && $toolCallId !== '') {
                $result['tool_call_id'] = $toolCallId;
            }
            $results[] = $result;
        }
        return $results;
    }
}
