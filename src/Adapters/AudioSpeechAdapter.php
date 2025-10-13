<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioSpeechException;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Audio Speech endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Audio Speech (text-to-speech) endpoint format.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class AudioSpeechAdapter implements EndpointAdapter
{
    /**
     * Transform unified request to OpenAI Audio Speech (TTS) format.
     *
     * Converts text to speech using OpenAI's text-to-speech models.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     * @throws AudioSpeechException If text is missing or empty
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $audio = is_array($unifiedRequest['audio'] ?? null) ? $unifiedRequest['audio'] : [];

        $text = $audio['text'] ?? null;
        $voice = $audio['voice'] ?? 'alloy';
        $speed = $audio['speed'] ?? 1.0;

        // Validate text - only check for missing or empty, not length
        if ($text === null || $text === '') {
            throw AudioSpeechException::missingText();
        }

        if (!is_string($text)) {
            $text = (string) $text;
        }

        if (trim($text) === '') {
            throw AudioSpeechException::emptyText();
        }

        return [
            'model' => $audio['model'] ?? 'tts-1',
            'input' => $text,
            'voice' => $voice,
            'response_format' => $audio['response_format'] ?? $audio['format'] ?? 'mp3',
            'speed' => $speed,
        ];
    }

    /**
     * Transform OpenAI Audio Speech response to unified ResponseDto.
     *
     * The response contains binary audio content.
     *
     * @param array{
     *     id?: string,
     *     content?: string,
     *     format?: string,
     *     voice?: string,
     *     model?: string,
     *     speed?: float|int
     * } $apiResponse
     * @return ResponseDto
     */
    public function transformResponse(array $apiResponse): ResponseDto
    {
        $id = isset($apiResponse['id']) ? (string) $apiResponse['id'] : 'audio_speech_' . Str::uuid()->toString();
        $audioContent = isset($apiResponse['content']) ? (string) $apiResponse['content'] : null;
        $format = isset($apiResponse['format']) ? (string) $apiResponse['format'] : 'mp3';
        $voice = isset($apiResponse['voice']) ? (string) $apiResponse['voice'] : null;
        $model = isset($apiResponse['model']) ? (string) $apiResponse['model'] : null;
        $speed = $apiResponse['speed'] ?? 1.0;

        return new ResponseDto(
            id: $id,
            status: 'completed',
            text: null,
            raw: $apiResponse,
            conversationId: null,
            audioContent: $audioContent,
            images: null,
            type: 'audio_speech',
            metadata: [
                'format' => $format,
                'voice' => $voice,
                'model' => $model,
                'speed' => $speed,
            ],
        );
    }
}
