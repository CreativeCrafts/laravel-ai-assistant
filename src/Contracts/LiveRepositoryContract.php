<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Live API: realtime WebRTC sessions, SIP call control and recordings.
 * Resolve through Ai::live().
 */
interface LiveRepositoryContract
{
    /**
     * Create a Live WebRTC session. Start with the Live prompting guide.
     *
     * POST /v1/live/sessions
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Accept an incoming SIP call. Supply session with type live, the model, and startup configuration.
     *
     * POST /v1/live/sessions/{sessionId}/accept
     *
     * @param array<string, mixed> $payload
     */
    public function accept(string $sessionId, array $payload): array;

    /**
     * End a SIP call identified by session_id.
     *
     * POST /v1/live/sessions/{sessionId}/hangup
     */
    public function hangup(string $sessionId): array;

    /**
     * Transfer a SIP call to another destination. Supply a nonblank target_uri for the SIP Refer-To header.
     *
     * POST /v1/live/sessions/{sessionId}/refer
     *
     * @param array<string, mixed> $payload
     */
    public function refer(string $sessionId, array $payload): array;

    /**
     * Reject an incoming SIP call. Send a required SIP rejection status_code between 300 and 699.
     *
     * POST /v1/live/sessions/{sessionId}/reject
     *
     * @param array<string, mixed> $payload
     */
    public function reject(string $sessionId, array $payload = []): array;

    /**
     * Fork a stored Live session onto a new WebRTC connection.
     *
     * POST /v1/live/sessions/{sessionId}/fork
     *
     * @param array<string, mixed> $payload
     */
    public function fork(string $sessionId, array $payload): array;

    /**
     * Get Live session content.
     *
     * GET /v1/live/sessions/{sessionId}/content
     *
     * @return array{content: string, content_type: string}
     */
    public function downloadRecording(string $sessionId): array;
}
