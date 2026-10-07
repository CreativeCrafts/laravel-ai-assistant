<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

/**
 * Verifies (and creates) webhook signatures in the Standard Webhooks format used by OpenAI:
 * the webhook-id, webhook-timestamp and webhook-signature headers, where the signature is
 * "v1,<base64 HMAC-SHA256 of '{id}.{timestamp}.{body}'>" (several may be space-separated).
 *
 * Secrets in the "whsec_<base64>" format are base64-decoded before signing, as OpenAI does.
 */
final class StandardWebhookSignature
{
    public const ID_HEADER = 'webhook-id';
    public const TIMESTAMP_HEADER = 'webhook-timestamp';
    public const SIGNATURE_HEADER = 'webhook-signature';

    /**
     * @param int $tolerance Maximum clock skew in seconds, in either direction
     */
    public static function verify(
        string $payload,
        string $webhookId,
        string $timestamp,
        string $signatureHeader,
        string $secret,
        int $tolerance = 300,
        ?int $now = null
    ): bool {
        if ($webhookId === '' || $signatureHeader === '' || !ctype_digit($timestamp)) {
            return false;
        }

        $now ??= time();
        if (abs($now - (int)$timestamp) > $tolerance) {
            return false;
        }

        $expected = self::sign($payload, $webhookId, $timestamp, $secret);
        if ($expected === null) {
            return false;
        }

        foreach (explode(' ', $signatureHeader) as $candidate) {
            $candidate = trim($candidate);
            if (str_starts_with($candidate, 'v1,')) {
                $candidate = substr($candidate, 3);
            }
            if ($candidate !== '' && hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The base64 signature for a payload (without the "v1," prefix), or null for an invalid whsec_ secret.
     */
    public static function sign(string $payload, string $webhookId, string $timestamp, string $secret): ?string
    {
        $key = $secret;
        if (str_starts_with($secret, 'whsec_')) {
            $decoded = base64_decode(substr($secret, strlen('whsec_')), true);
            if ($decoded === false) {
                return null;
            }
            $key = $decoded;
        }

        return base64_encode(hash_hmac('sha256', "{$webhookId}.{$timestamp}.{$payload}", $key, true));
    }
}
