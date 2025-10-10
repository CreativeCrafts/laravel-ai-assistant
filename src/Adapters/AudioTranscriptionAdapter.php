<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Adapter for OpenAI Audio Transcription endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Audio Transcription endpoint format.
 */
final class AudioTranscriptionAdapter implements EndpointAdapter
{
    /**
     * Transform unified request to OpenAI Audio Transcription format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     * @throws InvalidArgumentException If the audio file is invalid
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $audio = is_array($unifiedRequest['audio'] ?? null) ? $unifiedRequest['audio'] : [];
        $filePath = $audio['file'] ?? null;

        if ($filePath !== null) {
            $this->validateAudioFile($filePath);
        }

        return [
            'file' => $filePath,
            'model' => $audio['model'] ?? 'whisper-1',
            'language' => $audio['language'] ?? null,
            'prompt' => $audio['prompt'] ?? null,
            'response_format' => $audio['response_format'] ?? 'json',
            'temperature' => $audio['temperature'] ?? 0,
        ];
    }

    /**
     * Transform OpenAI Audio Transcription response to unified ResponseDto.
     *
     * @param array{
     *     id?: string,
     *     text?: string,
     *     duration?: float|int,
     *     language?: string
     * } $apiResponse
     * @return ResponseDto
     */
    public function transformResponse(array $apiResponse): ResponseDto
    {
        $id = isset($apiResponse['id']) ? (string) $apiResponse['id'] : 'audio_transcription_' . Str::uuid()->toString();
        $text = isset($apiResponse['text']) ? (string) $apiResponse['text'] : null;
        $duration = $apiResponse['duration'] ?? null;
        $language = isset($apiResponse['language']) ? (string) $apiResponse['language'] : null;

        return new ResponseDto(
            id: $id,
            status: 'completed',
            text: $text,
            raw: $apiResponse,
            conversationId: null,
            audioContent: null,
            images: null,
            type: 'audio_transcription',
            metadata: [
                'duration' => $duration,
                'language' => $language,
            ],
        );
    }

    /**
     * Validate that the audio file exists, is readable, and has a supported format.
     *
     * @param mixed $filePath
     * @throws InvalidArgumentException If the file is invalid
     */
    private function validateAudioFile(mixed $filePath): void
    {
        if (!is_string($filePath)) {
            throw new InvalidArgumentException('Audio file path must be a string.');
        }

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Audio file does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("Audio file is not readable: {$filePath}");
        }

        $supportedFormats = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, $supportedFormats, true)) {
            throw new InvalidArgumentException(
                "Unsupported audio format: {$extension}. Supported formats: " . implode(', ', $supportedFormats)
            );
        }
    }
}
