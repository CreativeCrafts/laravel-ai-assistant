<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Http\Controllers;

use CreativeCrafts\LaravelAiAssistant\Enums\ResponseStatus;
use CreativeCrafts\LaravelAiAssistant\Events\OpenAiWebhookReceived;
use CreativeCrafts\LaravelAiAssistant\Events\ResponseCompleted;
use CreativeCrafts\LaravelAiAssistant\Events\ResponseFailed;
use CreativeCrafts\LaravelAiAssistant\Events\ToolCallRequested;
use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;
use CreativeCrafts\LaravelAiAssistant\Support\StandardWebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

readonly class WebhookController
{
    public function __construct(private ResponseStatusStore $statusStore)
    {
    }

    /**
     * Handles incoming webhook requests from AI assistant services.
     *
     * This method processes webhook payloads by:
     * - Validating webhook configuration and security settings
     * - Verifying request signatures using HMAC-SHA256 with optional timestamp-based replay protection
     * - Parsing and validating the JSON payload
     * - Dispatching appropriate events based on the webhook event type
     * - Updating response status in the status store
     *
     * @param Request $request The incoming HTTP request containing the webhook payload and headers
     *
     * @return JsonResponse Returns a JSON response indicating success or failure:
     *                      - 200 with {'OK': true} on successful processing
     *                      - 404 with an error message if webhooks are disabled
     *                      - 403 with an error message if signing secret is not configured
     *                      - 400 with error message for missing signature header or invalid JSON
     *                      - 401 with an error message for invalid signature verification
     *                      - 422 with an error message if response ID cannot be extracted from payload
     *
     * @throws JsonException When JSON decoding fails due to a malformed payload
     */
    public function handle(Request $request): JsonResponse
    {
        if (!Config::boolean(key: 'ai-assistant.webhooks.enabled', default: false)) {
            return response()->json(
                data: ['error' => 'Webhooks disabled'],
                status: Response::HTTP_NOT_FOUND
            );
        }

        $secret = Config::string(key: 'ai-assistant.webhooks.signing_secret');
        if (!is_string($secret) || $secret === '') {
            return response()->json(
                data: ['error' => 'Signing secret not configured'],
                status: Response::HTTP_FORBIDDEN
            );
        }

        $raw = $request->getContent();
        // env() yields strings (e.g. "600"), so accept any positive numeric value like the middleware does
        $skewValue = Config::get('ai-assistant.webhooks.max_skew_seconds', 300);
        $skew = is_numeric($skewValue) && (int)$skewValue > 0 ? (int)$skewValue : 300;

        $webhookId = (string) $request->header(StandardWebhookSignature::ID_HEADER, '');
        $webhookTimestamp = (string) $request->header(StandardWebhookSignature::TIMESTAMP_HEADER, '');
        $webhookSignature = (string) $request->header(StandardWebhookSignature::SIGNATURE_HEADER, '');

        if ($webhookId !== '' && $webhookTimestamp !== '' && $webhookSignature !== '') {
            // OpenAI webhooks are signed with the Standard Webhooks scheme (whsec_ signing secrets)
            if (!StandardWebhookSignature::verify($raw, $webhookId, $webhookTimestamp, $webhookSignature, $secret, $skew)) {
                return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
            }
        } else {
            $legacyFailure = $this->verifyLegacySignature($request, $raw, $secret, $skew);
            if ($legacyFailure !== null) {
                return $legacyFailure;
            }
        }

        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            return response()->json(
                data: ['error' => 'Invalid JSON'],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $eventType = $data['type'] ?? $data['event'] ?? null;
        $eventData = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
        if (is_string($eventType) && $eventType !== '') {
            $eventId = ($data['object'] ?? null) === 'event' && is_string($data['id'] ?? null) ? $data['id'] : null;
            Event::dispatch(new OpenAiWebhookReceived($eventType, $eventData, $data, $eventId));

            // Batch, fine-tuning, eval and realtime events are only dispatched; they carry no response status
            if (!str_starts_with($eventType, 'response.')) {
                return response()->json(['ok' => true]);
            }
        }

        $responseId = $data['response']['id'] ?? $data['data']['response']['id'] ?? $data['response_id'] ?? '';
        if ($responseId === '' && ($data['object'] ?? null) === 'event') {
            // OpenAI webhook events carry the response id in data.id
            $responseId = $eventData['id'] ?? '';
        }
        if ($responseId === '') {
            // Attempt other fallbacks
            $responseId = $data['id'] ?? '';
        }
        $responseId = is_scalar($responseId) ? (string) $responseId : '';

        if ($responseId === '') {
            return response()->json(
                data: ['error' => 'Missing response id'],
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        switch ($eventType) {
            case 'response.completed':
                $this->statusStore->setStatus($responseId, ResponseStatus::Completed->value, $data);
                Event::dispatch(new ResponseCompleted($responseId, $data));
                break;

            case 'response.failed':
                $error = $data['error']['message'] ?? ($data['response']['error']['message'] ?? null);
                $this->statusStore->setStatus($responseId, ResponseStatus::Failed->value, $data);
                Event::dispatch(new ResponseFailed($responseId, $error, $data));
                break;

            case 'response.cancelled':
                $this->statusStore->setStatus($responseId, 'cancelled', $data);
                break;

            case 'response.incomplete':
                $this->statusStore->setStatus($responseId, 'incomplete', $data);
                break;

            case 'response.required_action':
            case 'response.tool_call.created':
            case 'response.tool_call.required':
                $toolCalls = $this->extractToolCalls($data);
                $this->statusStore->setStatus($responseId, ResponseStatus::RequiresAction->value, $data);
                Event::dispatch(new ToolCallRequested($responseId, $toolCalls, $data));
                break;

            default:
                // Store unknown types for observability but do not error
                $this->statusStore->setStatus($responseId, $eventType ?? ResponseStatus::Unknown->value, $data);
                break;
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Verify the package's original HMAC scheme (configurable signature/timestamp headers).
     * Returns an error response when verification fails, or null when the request is authentic.
     */
    private function verifyLegacySignature(Request $request, string $raw, string $secret, int $skew): ?JsonResponse
    {
        $signatureHeader = Config::string(key: 'ai-assistant.webhooks.signature_header', default: 'X-OpenAI-Signature');
        $timestampHeader = Config::string(key: 'ai-assistant.webhooks.timestamp_header', default: 'X-OpenAI-Timestamp');
        $requireTimestamp = Config::boolean(key: 'ai-assistant.webhooks.require_timestamp', default: false);

        $provided = (string) $request->header($signatureHeader, '');
        if ($provided === '') {
            return response()->json(
                data:['error' => 'Missing signature header'],
                status: Response::HTTP_BAD_REQUEST
            );
        }
        $timestamp = (string) $request->header($timestampHeader, '');
        if ($requireTimestamp && ($timestamp === '' || !ctype_digit($timestamp))) {
            return response()->json(
                data: ['error' => 'Missing or invalid timestamp'],
                status: Response::HTTP_UNAUTHORIZED
            );
        }

        // Header may be in format "sha256=..." or raw hex
        $normalized = str_starts_with($provided, 'sha256=') ? substr($provided, 7) : $provided;

        $verified = false;
        if ($timestamp !== '' && ctype_digit($timestamp)) {
            // Enforce replay protection when a valid timestamp is provided
            $now = time();
            $ts = (int) $timestamp;
            if (abs($now - $ts) <= $skew) {
                $toSign = $timestamp . '.' . $raw;
                $expected = hash_hmac('sha256', $toSign, $secret);
                $verified = hash_equals($expected, $normalized);
            }
        }

        if (!$verified && $requireTimestamp) {
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$verified) {
            // Backward-compatibility: accept legacy body-only signature when timestamp missing/invalid or a new scheme fails
            $legacyExpected = hash_hmac('sha256', $raw, $secret);
            if (!hash_equals($legacyExpected, $normalized)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        return null;
    }

    /**
     * Attempt to extract tool calls from various payload shapes.
     */
    private function extractToolCalls(array $data): array
    {
        // Common locations
        $toolCalls = $data['response']['required_action']['submit_tool_outputs']['tool_calls']
            ?? $data['data']['response']['required_action']['submit_tool_outputs']['tool_calls']
            ?? $data['tool_calls']
            ?? [];

        // Ensure $toolCalls is always an array
        if (!is_array($toolCalls)) {
            $toolCalls = [];
        }

        if (empty($toolCalls) && isset($data['response']['output']) && is_array($data['response']['output'])) {
            foreach ($data['response']['output'] as $block) {
                if (is_array($block) && ($block['type'] ?? '') === 'tool_call' && isset($block['tool_call']) && is_array($block['tool_call'])) {
                    $toolCalls[] = $block['tool_call'];
                }
            }
        }

        return $toolCalls;
    }
}
