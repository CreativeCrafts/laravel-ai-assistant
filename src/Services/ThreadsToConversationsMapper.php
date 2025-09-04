<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use InvalidArgumentException;

/**
 * Backward-compatibility mapper from legacy thread_id to new conversation_id.
 * Provides in-memory fast path with optional cache-backed persistence across requests.
 */
final class ThreadsToConversationsMapper
{
    private const CACHE_PREFIX = 'threads_to_conversations:';

    /** @var array<string,string> */
    private array $map = [];

    public function __construct(private readonly CacheService $cache)
    {
    }

    /**
     * Persist a mapping between a legacy thread id and a conversation id.
     */
    public function map(string $threadId, string $conversationId, int $ttl = 86400): void
    {
        if ($threadId === '' || $conversationId === '') {
            throw new InvalidArgumentException('threadId and conversationId must be non-empty');
        }
        $this->map[$threadId] = $conversationId;
        // also store in cache for cross-request reuse
        $this->cache->cacheResponse(self::CACHE_PREFIX . $threadId, ['conversation_id' => $conversationId], $ttl);
    }

    /**
     * Get a mapped conversation id if available.
     */
    public function get(string $threadId): ?string
    {
        if ($threadId === '') {
            throw new InvalidArgumentException('threadId must be non-empty');
        }
        if (isset($this->map[$threadId])) {
            return $this->map[$threadId];
        }
        $cached = $this->cache->getResponse(self::CACHE_PREFIX . $threadId);
        if ($cached !== null && isset($cached['conversation_id']) && is_string($cached['conversation_id'])) {
            $this->map[$threadId] = $cached['conversation_id'];
            return $cached['conversation_id'];
        }
        return null;
    }

    /**
     * Get or create a conversation id for a given thread id.
     *
     * @param callable():string $createConversation callback that returns a conversation id
     */
    public function getOrMap(string $threadId, callable $createConversation, int $ttl = 86400): string
    {
        $existing = $this->get($threadId);
        if ($existing !== null) {
            return $existing;
        }
        $conversationId = (string)$createConversation();
        if ($conversationId === '') {
            throw new InvalidArgumentException('createConversation must return a non-empty conversationId');
        }
        $this->map($threadId, $conversationId, $ttl);
        return $conversationId;
    }
}
