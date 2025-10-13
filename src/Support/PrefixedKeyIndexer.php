<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use Illuminate\Contracts\Cache\Repository;

/**
 * Maintains an index of cache keys per logical prefix, to enable prefix purges
 * on stores that do not support tags.
 *
 * @internal Used internally for cache key indexing.
 * Do not use directly.
 */
final readonly class PrefixedKeyIndexer
{
    public function __construct(
        private Repository $store,
        private string $globalPrefix,
        private int $indexTtl
    ) {
    }

    /**
     * Add a fully qualified cache key to the index for a logical prefix.
     */
    public function add(string $logicalPrefix, string $fullKey): void
    {
        $indexKey = $this->indexKey($logicalPrefix);
        $list = $this->getList($logicalPrefix);
        if (!in_array($fullKey, $list, true)) {
            $list[] = $fullKey;
            $this->store->put($indexKey, $list, $this->indexTtl);
        }
    }

    /**
     * Remove a fully qualified cache key from the index for a logical prefix.
     */
    public function remove(string $logicalPrefix, string $fullKey): void
    {
        $indexKey = $this->indexKey($logicalPrefix);
        $list = array_values(array_filter($this->getList($logicalPrefix), fn ($k) => $k !== $fullKey));
        $this->store->put($indexKey, $list, $this->indexTtl);
    }

    /**
     * List fully qualified cache keys for the logical prefix.
     *
     * @return array<int, string>
     */
    public function list(string $logicalPrefix): array
    {
        return $this->getList($logicalPrefix);
    }

    /**
     * Clear and return the number of keys removed from the index for the given logical prefix.
     */
    public function clear(string $logicalPrefix): int
    {
        $indexKey = $this->indexKey($logicalPrefix);
        $list = $this->getList($logicalPrefix);
        $this->store->forget($indexKey);
        return count($list);
    }

    private function indexKey(string $logicalPrefix): string
    {
        return rtrim($this->globalPrefix, ':') . ':' . 'index:' . $logicalPrefix;
    }

    private function getList(string $logicalPrefix): array
    {
        $indexKey = $this->indexKey($logicalPrefix);
        $list = $this->store->get($indexKey, []);
        return is_array($list) ? $list : [];
    }
}
