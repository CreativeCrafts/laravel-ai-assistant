<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\AudioRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Support\MultipartFormData;
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
