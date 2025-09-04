<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use JsonException;

final class IdempotencyService
{
    /**
     * Build a deterministic idempotency key from a request payload and a time bucket.
     * Strategy: sha256 of normalized JSON payload + current bucket timestamp.
     * - Bucket seconds default to 60 (config: ai-assistant.responses.idempotency_bucket)
     * - Normalization uses JSON_UNESCAPED_* and sorted keys to ensure stable hashes
     *
     * @throws JsonException
     */
    public function buildKey(array $payload, ?int $bucketSeconds = null): string
    {
        $configValue = config('ai-assistant.responses.idempotency_bucket', 60);
        $bucket = $bucketSeconds ?? (is_numeric($configValue) ? (int) $configValue : 60);
        if ($bucket < 1) {
            $bucket = 60;
        }
        $nowBucket = (int) floor(time() / $bucket);
        $normalized = $this->stableJson($payload);
        $hash = hash('sha256', $normalized . '|' . $nowBucket);
        // Prefix for clarity and potential routing/debugging
        return 'resp_' . $nowBucket . '_' . substr($hash, 0, 32);
    }

    /**
     * @throws JsonException
     */
    private function stableJson(array $payload): string
    {
        $sorted = $this->ksortRecursive($payload);
        return json_encode($sorted, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function ksortRecursive(array $array): array
    {
        // Sort keys for deterministic encoding
        ksort($array);
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = $this->ksortRecursive($v);
            }
        }
        return $array;
    }
}
