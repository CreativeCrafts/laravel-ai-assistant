<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use Closure;
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use stdClass;

/**
 * @internal Used internally for building tool configurations.
 * Do not use directly.
 */
final class ToolsBuilder
{
    private array $config = [];

    public function __construct()
    {
    }

    public function includeFunctionCallTool(string $functionName, string $functionDescription, array|FunctionCallParameterContract $functionParameters, bool $isStrict = false): self
    {
        $allow = config('ai-assistant.tools.allowlist');
        if (is_array($allow) && $allow !== [] && !in_array($functionName, $allow, true)) {
            throw new InvalidArgumentException("Disallowed tool. Configure ai-assistant.tools.allowlist to include '{$functionName}'.");
        }

        if ($functionParameters instanceof FunctionCallParameterContract) {
            $functionParameters = $functionParameters->toArray();
        }

        $tool = [
            'type' => 'function',
            'function' => [
                'name' => $functionName,
                'description' => $functionDescription,
                'parameters' => $functionParameters !== [] ? [
                    'type' => 'object',
                    'properties' => $functionParameters['properties'] ?? [],
                    'required' => $functionParameters['required'] ?? [],
                    'additionalProperties' => $functionParameters['additionalProperties'] ?? false,
                ] : new stdClass(),
                'strict' => $isStrict,
            ],
        ];

        $tools = (array)($this->config['tools'] ?? []);
        foreach ($tools as $t) {
            if (($t['type'] ?? null) === 'function' && ($t['function']['name'] ?? null) === $functionName) {
                Log::warning('[ToolsBuilder] includeFunctionCallTool: duplicate function tool skipped', ['function' => $functionName]);
                return $this;
            }
        }

        $tools[] = $tool;
        $this->config['tools'] = $tools;

        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function includeFunctionFromCallable(callable $fn, ?string $exportedName = null, string $description = '', bool $isStrict = false): self
    {
        $name = $exportedName;
        $ref = null;
        if (is_array($fn) && count($fn) === 2) {
            [$objOrClass, $method] = $fn;
            $ref = new ReflectionMethod($objOrClass, $method);
            $name = $name ?? (is_string($objOrClass) ? $objOrClass . '::' . $method : get_class($objOrClass) . '::' . $method);
        } elseif ($fn instanceof Closure) {
            $ref = new ReflectionFunction($fn);
            $name = $name ?? ($ref->getName() ?: 'closure');
        } elseif (is_string($fn)) {
            $ref = new ReflectionFunction($fn);
            $name = $name ?? $fn;
        } elseif (is_object($fn) && method_exists($fn, '__invoke')) {
            $ref = new ReflectionMethod($fn, '__invoke');
            $name = $name ?? (get_class($fn) . '::__invoke');
        } else {
            throw new InvalidArgumentException('Unsupported callable type for includeFunctionFromCallable');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$name);
        $safeName = substr($safeName ?? '', 0, 64);

        $properties = [];
        $required = [];
        foreach ($ref->getParameters() as $p) {
            $propType = 'string';
            $t = $p->getType();
            if ($t instanceof ReflectionNamedType) {
                $tn = $t->getName();
                $propType = match ($tn) {
                    'string' => 'string',
                    'int' => 'integer',
                    'float', 'double' => 'number',
                    'bool' => 'boolean',
                    'array' => 'array',
                    default => 'string',
                };
            }
            $schema = ['type' => $propType];
            if ($propType === 'array') {
                $schema['items'] = ['type' => 'string'];
            }
            $properties[$p->getName()] = $schema;
            if (!$p->isOptional()) {
                $required[] = $p->getName();
            }
        }

        $params = [
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ];

        return $this->includeFunctionCallTool($safeName ?: 'function', $description, $params, $isStrict);
    }

    public function includeFileSearchTool(array $vectorStoreIds = []): self
    {
        $tools = (array)($this->config['tools'] ?? []);
        $already = false;
        foreach ($tools as $t) {
            if (($t['type'] ?? null) === 'file_search') {
                $already = true;
                break;
            }
        }
        if ($already) {
            Log::warning('[ToolsBuilder] includeFileSearchTool: duplicate file_search tool skipped');
        } else {
            $tools[] = ['type' => 'file_search'];
        }
        $this->config['tools'] = $tools;

        if ($vectorStoreIds !== []) {
            $this->config['tool_resources'] = array_merge(
                $this->config['tool_resources'] ?? [],
                [
                    'file_search' => [
                        'vector_store_ids' => $vectorStoreIds,
                    ]
                ]
            );
        }

        return $this;
    }

    public function includeCodeInterpreterTool(array $fileIds = []): self
    {
        $tools = (array)($this->config['tools'] ?? []);
        $has = false;
        foreach ($tools as $t) {
            if (($t['type'] ?? null) === 'code_interpreter') {
                $has = true;
                break;
            }
        }
        if ($has) {
            Log::warning('[ToolsBuilder] includeCodeInterpreterTool: duplicate code_interpreter tool skipped');
        } else {
            $tools[] = ['type' => 'code_interpreter'];
        }
        $this->config['tools'] = $tools;

        if ($fileIds !== []) {
            $resources = (array)($this->config['tool_resources'] ?? []);
            $ci = (array)($resources['code_interpreter'] ?? []);
            $existing = array_values(array_filter((array)($ci['file_ids'] ?? []), 'is_string'));
            $merged = array_values(array_unique(array_merge($existing, array_values(array_filter($fileIds, 'is_string')))));
            $resources['code_interpreter'] = ['file_ids' => $merged];
            $this->config['tool_resources'] = $resources;
        }

        return $this;
    }

    public function setToolChoice(string|array $choice): self
    {
        $this->config['tool_choice'] = $choice;
        return $this;
    }

    public function useFileSearch(bool $enabled = true): self
    {
        $this->config['use_file_search'] = $enabled;
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
