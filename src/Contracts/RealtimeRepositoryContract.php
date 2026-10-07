<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Realtime API: ephemeral client secrets, WebRTC/SIP calls and translation sessions.
 * Resolve through Ai::realtime().
 */
interface RealtimeRepositoryContract
{
    /**
     * Create a Realtime client secret with an associated session configuration.
     *
     * POST /v1/realtime/client_secrets
     *
     * @param array<string, mixed> $payload
     */
    public function createClientSecret(array $payload = []): array;

    /**
     * Accept an incoming SIP call and configure the realtime session that will handle it.
     *
     * POST /v1/realtime/calls/{callId}/accept
     *
     * @param array<string, mixed> $payload
     */
    public function acceptCall(string $callId, array $payload): array;

    /**
     * End an active Realtime API call, whether it was initiated over SIP or WebRTC.
     *
     * POST /v1/realtime/calls/{callId}/hangup
     */
    public function hangupCall(string $callId): array;

    /**
     * Transfer an active SIP call to a new destination using the SIP REFER verb.
     *
     * POST /v1/realtime/calls/{callId}/refer
     *
     * @param array<string, mixed> $payload
     */
    public function referCall(string $callId, array $payload): array;

    /**
     * Decline an incoming SIP call by returning a SIP status code to the caller.
     *
     * POST /v1/realtime/calls/{callId}/reject
     *
     * @param array<string, mixed> $payload
     */
    public function rejectCall(string $callId, array $payload = []): array;

    /**
     * Create a Realtime translation client secret with an associated translation session configuration.
     *
     * POST /v1/realtime/translations/client_secrets
     *
     * @param array<string, mixed> $payload
     */
    public function createTranslationClientSecret(array $payload = []): array;

    /**
     * Create an ephemeral API token for use in client-side applications with the Realtime API specifically for realtime transcriptions.
     *
     * POST /v1/realtime/transcription_sessions
     *
     * @param array<string, mixed> $payload
     */
    public function createTranscriptionSession(array $payload = []): array;

    /**
     * Create a new Realtime API call over WebRTC and receive the SDP answer needed to complete the peer connection.
     *
     * POST /v1/realtime/calls
     *
     * @param string $sdp The WebRTC SDP offer from the client
     * @param array<string, mixed> $session Optional session configuration (model, instructions, audio, tools, ...)
     * @return array{sdp: string, call_id: string|null} The SDP answer and the call ID (from the Location header)
     */
    public function createCall(string $sdp, array $session = []): array;
}
