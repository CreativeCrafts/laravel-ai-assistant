<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\AudioRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Support\MultipartFormData;
use CreativeCrafts\LaravelAiAssistant\Support\PathSegment;
use CreativeCrafts\LaravelAiAssistant\Support\ServerSentEvents;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal Use Ai::audio() or Ai::diarize() instead.
 */
final readonly class AudioHttpRepository implements AudioRepositoryContract
{
    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    public function createSpeech(array $payload): array
    {
        return $this->transport->request('POST', $this->endpoint('audio/speech'), [
            'json' => $payload,
            'headers' => ['Accept' => 'application/octet-stream'],
            'timeout' => $this->timeout('speech'),
        ]);
    }

    public function streamSpeech(array $payload): iterable
    {
        $payload['stream_format'] = 'sse';

        yield from ServerSentEvents::decode($this->transport->streamRequest('POST', $this->endpoint('audio/speech'), [
            'json' => $payload,
            'timeout' => $this->timeout('speech'),
        ]));
    }

    public function createTranscription(array $payload): array
    {
        unset($payload['stream']);

        return $this->transport->request('POST', $this->endpoint('audio/transcriptions'), [
            'multipart' => MultipartFormData::encode($payload, ['file']),
            'timeout' => $this->timeout('transcription'),
        ]);
    }

    public function streamTranscription(array $payload): iterable
    {
        $payload['stream'] = true;

        yield from ServerSentEvents::decode($this->transport->streamRequest('POST', $this->endpoint('audio/transcriptions'), [
            'multipart' => MultipartFormData::encode($payload, ['file']),
            'timeout' => $this->timeout('transcription'),
        ]));
    }

    public function createTranslation(array $payload): array
    {
        return $this->transport->request('POST', $this->endpoint('audio/translations'), [
            'multipart' => MultipartFormData::encode($payload, ['file']),
            'timeout' => $this->timeout('translation'),
        ]);
    }

    public function createVoice(array $payload): array
    {
        return $this->transport->request('POST', $this->endpoint('audio/voices'), [
            'multipart' => MultipartFormData::encode($payload, ['audio_sample']),
        ]);
    }

    public function createVoiceConsent(array $payload): array
    {
        return $this->transport->request('POST', $this->endpoint('audio/voice_consents'), [
            'multipart' => MultipartFormData::encode($payload, ['recording']),
        ]);
    }

    public function listVoiceConsents(array $params = []): array
    {
        return $this->transport->request('GET', $this->endpoint('audio/voice_consents'), ['query' => $params]);
    }

    public function retrieveVoiceConsent(string $consentId): array
    {
        return $this->transport->request('GET', $this->voiceConsentEndpoint($consentId));
    }

    public function updateVoiceConsent(string $consentId, array $payload): array
    {
        return $this->transport->request('POST', $this->voiceConsentEndpoint($consentId), ['json' => $payload]);
    }

    public function deleteVoiceConsent(string $consentId): array
    {
        return $this->transport->request('DELETE', $this->voiceConsentEndpoint($consentId));
    }

    private function voiceConsentEndpoint(string $consentId): string
    {
        return $this->endpoint('audio/voice_consents/' . PathSegment::encode($consentId));
    }

    private function timeout(string $operation): ?float
    {
        $timeout = config("ai-assistant.audio.timeouts.{$operation}");

        return is_numeric($timeout) && (float)$timeout > 0 ? (float)$timeout : null;
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
