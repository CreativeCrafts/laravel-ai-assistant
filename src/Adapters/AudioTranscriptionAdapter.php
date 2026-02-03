<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\Contracts\Adapters\AudioEndpointAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranscriptionException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Audio Transcription endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Audio Transcription endpoint format.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class AudioTranscriptionAdapter implements AudioEndpointAdapter
{
    /**
     * Transform unified request to OpenAI Audio Transcription format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     * @throws AudioTranscriptionException If the audio file is invalid
     * @throws FileValidationException If file validation fails
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $audio = is_array($unifiedRequest['audio'] ?? null) ? $unifiedRequest['audio'] : [];
        $filePath = $audio['file'] ?? null;

        if ($filePath === null) {
            throw AudioTranscriptionException::missingFile();
        }

        $this->validateAudioFile($filePath);

        return [
            'file' => $filePath,
            'model' => $audio['model'] ?? 'gpt-4o-mini-transcribe',
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
     * @throws FileValidationException If the file is invalid
     * @throws AudioTranscriptionException If the audio file validation fails
     */
    private function validateAudioFile(mixed $filePath): void
    {
        if (!is_string($filePath)) {
            throw FileValidationException::invalidPathType($filePath);
        }

        if (!file_exists($filePath)) {
            throw FileValidationException::fileNotFound($filePath);
        }

        if (!is_readable($filePath)) {
            throw FileValidationException::fileNotReadable($filePath);
        }

        $supportedFormats = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, $supportedFormats, true)) {
            throw AudioTranscriptionException::unsupportedFormat($filePath, $extension);
        }

        $fileSize = filesize($filePath);
        if ($fileSize !== false && $fileSize > 25 * 1024 * 1024) {
            throw AudioTranscriptionException::fileTooLarge($filePath, $fileSize);
        }
    }
}
