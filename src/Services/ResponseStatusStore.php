<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use InvalidArgumentException;

class ResponseStatusStore
{
    private const STATUS_PREFIX = 'webhook:response_status:';
    private const CONVERSATION_PREFIX = 'webhook:conversation_status:';

    public function __construct(private CacheService $cache)
    {
    }

    public function setStatus(string $responseId, string $status, array $payload = [], int $ttl = 86400): void
    {
        if ($responseId === '') {
            throw new InvalidArgumentException('responseId cannot be empty');
        }
        $record = [
            'status' => $status,
            'payload' => $payload,
            'updated_at' => time(),
        ];
        $key = self::STATUS_PREFIX . $responseId;
        $this->cache->cacheResponse($key, $record, $ttl);

        // Also index by conversation id if available
        $conversationId = (string)(
            $payload['response']['conversation_id']
            ?? $payload['data']['response']['conversation_id']
            ?? $payload['conversation_id']
            ?? ($payload['conversation']['id'] ?? '')
        );
        if ($conversationId !== '') {
            $convRecord = $record;
            $convRecord['last_response_id'] = $responseId;
            $convKey = self::CONVERSATION_PREFIX . $conversationId;
            $this->cache->cacheResponse($convKey, $convRecord, $ttl);
        }
    }

    public function getStatus(string $responseId): ?array
    {
        if ($responseId === '') {
            throw new InvalidArgumentException('responseId cannot be empty');
        }
        $key = self::STATUS_PREFIX . $responseId;
        return $this->cache->getResponse($key);
    }

    public function getLastStatus(string $responseId): ?string
    {
        $entry = $this->getStatus($responseId);
        return is_array($entry) ? ($entry['status'] ?? null) : null;
    }

    public function getByConversationId(string $conversationId): ?array
    {
        if ($conversationId === '') {
            throw new InvalidArgumentException('conversationId cannot be empty');
        }
        $convKey = self::CONVERSATION_PREFIX . $conversationId;
        return $this->cache->getResponse($convKey);
    }

    public function getLastStatusByConversation(string $conversationId): ?string
    {
        $entry = $this->getByConversationId($conversationId);
        return is_array($entry) ? ($entry['status'] ?? null) : null;
    }
}
