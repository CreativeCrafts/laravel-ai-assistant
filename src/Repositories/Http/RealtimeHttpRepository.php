<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\RealtimeRepositoryContract;

/**
 * @internal Use Ai::realtime() instead.
 */
final readonly class RealtimeHttpRepository extends AbstractHttpRepository implements RealtimeRepositoryContract
{
    public function createClientSecret(array $payload = []): array
    {
        return $this->postJson('realtime/client_secrets', $payload);
    }

    public function acceptCall(string $callId, array $payload): array
    {
        return $this->postJson($this->path('realtime/calls/%s/accept', $callId), $payload);
    }

    public function hangupCall(string $callId): array
    {
        return $this->postJson($this->path('realtime/calls/%s/hangup', $callId));
    }

    public function referCall(string $callId, array $payload): array
    {
        return $this->postJson($this->path('realtime/calls/%s/refer', $callId), $payload);
    }

    public function rejectCall(string $callId, array $payload = []): array
    {
        return $this->postJson($this->path('realtime/calls/%s/reject', $callId), $payload);
    }

    public function createTranslationClientSecret(array $payload = []): array
    {
        return $this->postJson('realtime/translations/client_secrets', $payload);
    }

    public function createTranscriptionSession(array $payload = []): array
    {
        return $this->postJson('realtime/transcription_sessions', $payload);
    }

    public function createCall(string $sdp, array $session = []): array
    {
        $parts = [['name' => 'sdp', 'contents' => $sdp, 'headers' => ['Content-Type' => 'application/sdp']]];
        if ($session !== []) {
            $parts[] = ['name' => 'session', 'contents' => (string)json_encode($session), 'headers' => ['Content-Type' => 'application/json']];
        }

        $response = $this->transport->request('POST', $this->endpoint('realtime/calls'), [
            'multipart' => $parts,
            'headers' => ['Accept' => 'application/sdp'],
        ]);

        $answer = $response['content'] ?? ($response['text'] ?? '');
        $location = $response['location'] ?? null;

        return [
            'sdp' => is_string($answer) ? $answer : '',
            'call_id' => is_string($location) && $location !== '' ? basename($location) : null,
        ];
    }
}
