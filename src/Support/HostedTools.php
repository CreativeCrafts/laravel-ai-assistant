<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

/**
 * Adds OpenAI-hosted tools to a tools list in the Responses API shape, merging ids into a tool that is
 * already present instead of adding a second one.
 *
 * @internal Used by the chat helpers (AiAssistant, ToolsBuilder).
 */
final class HostedTools
{
    /**
     * Add or update the file_search tool: {type: file_search, vector_store_ids: [...]}.
     *
     * @param array<int|string, mixed> $tools
     * @param array<int|string, mixed> $vectorStoreIds
     * @return array<int|string, mixed>
     */
    public static function withFileSearch(array $tools, array $vectorStoreIds): array
    {
        $ids = self::strings($vectorStoreIds);
        foreach ($tools as $i => $tool) {
            if (is_array($tool) && ($tool['type'] ?? null) === 'file_search') {
                $tool['vector_store_ids'] = self::merge($tool['vector_store_ids'] ?? [], $ids);
                $tools[$i] = $tool;
                return $tools;
            }
        }

        $tools[] = ['type' => 'file_search', 'vector_store_ids' => $ids];
        return $tools;
    }

    /**
     * Add or update the code_interpreter tool: {type: code_interpreter, container: {type: auto, file_ids: [...]}}.
     * A container given as an id (string) is kept as is.
     *
     * @param array<int|string, mixed> $tools
     * @param array<int|string, mixed> $fileIds
     * @return array<int|string, mixed>
     */
    public static function withCodeInterpreter(array $tools, array $fileIds): array
    {
        $ids = self::strings($fileIds);
        foreach ($tools as $i => $tool) {
            if (is_array($tool) && ($tool['type'] ?? null) === 'code_interpreter') {
                $container = $tool['container'] ?? ['type' => 'auto'];
                if (is_array($container)) {
                    $merged = self::merge($container['file_ids'] ?? [], $ids);
                    if ($merged !== []) {
                        $container['file_ids'] = $merged;
                    }
                    $tool['container'] = $container;
                    $tools[$i] = $tool;
                }
                return $tools;
            }
        }

        $container = ['type' => 'auto'];
        if ($ids !== []) {
            $container['file_ids'] = $ids;
        }
        $tools[] = ['type' => 'code_interpreter', 'container' => $container];
        return $tools;
    }

    /**
     * Whether the list already contains a tool of this type.
     *
     * @param array<int|string, mixed> $tools
     */
    public static function has(array $tools, string $type): bool
    {
        foreach ($tools as $tool) {
            if (is_array($tool) && ($tool['type'] ?? null) === $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<int, string>
     */
    private static function strings(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn ($v): bool => is_string($v) && $v !== '')));
    }

    /**
     * @return array<int, string>
     */
    private static function merge(mixed $existing, array $ids): array
    {
        return self::strings(array_merge(is_array($existing) ? $existing : [], $ids));
    }
}
