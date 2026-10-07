<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\Contracts\Adapters\AudioEndpointAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedSegment;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedTranscription;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranscriptionException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Support\DiarizationBuilder;
use CreativeCrafts\LaravelAiAssistant\Support\MultipartFormData;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SplFileInfo;

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

        if ($this->isDiarizationRequest($audio)) {
            return $this->transformDiarizationRequest($audio, $filePath);
        }

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

        $metadata = [
            'duration' => $duration,
            'language' => $language,
        ];

        if (DiarizedTranscription::isDiarized($apiResponse)) {
            $diarization = DiarizedTranscription::fromArray($apiResponse);
            $metadata['speakers'] = $diarization->speakers();
            $metadata['speaking_time'] = $diarization->speakingTime();
            $metadata['segments'] = array_map(
                static fn (DiarizedSegment $segment): array => $segment->toArray(),
                $diarization->segments
            );
        }

        return new ResponseDto(
            id: $id,
            status: 'completed',
            text: $text,
            raw: $apiResponse,
            conversationId: null,
            audioContent: null,
            images: null,
            type: 'audio_transcription',
            metadata: $metadata,
        );
    }

    /**
     * Speaker diarization is requested with 'diarize' => true, the diarize model, or the diarized_json format.
     *
     * @param array<mixed> $audio
     */
    private function isDiarizationRequest(array $audio): bool
    {
        return ($audio['diarize'] ?? false) === true
            || ($audio['model'] ?? null) === DiarizationBuilder::DEFAULT_MODEL
            || ($audio['response_format'] ?? null) === DiarizationBuilder::RESPONSE_FORMAT;
    }

    /**
     * Build a gpt-4o-transcribe-diarize request. The diarize model does not accept a prompt.
     *
     * @param array<mixed> $audio
     * @return array<string, mixed>
     */
    private function transformDiarizationRequest(array $audio, string $filePath): array
    {
        $request = [
            'file' => $filePath,
            'model' => $audio['model'] ?? DiarizationBuilder::DEFAULT_MODEL,
            'response_format' => $audio['response_format'] ?? DiarizationBuilder::RESPONSE_FORMAT,
            'chunking_strategy' => $audio['chunking_strategy'] ?? 'auto',
            'language' => $audio['language'] ?? null,
            'temperature' => $audio['temperature'] ?? null,
        ];

        $knownSpeakers = $audio['known_speakers'] ?? [];
        if (!is_array($knownSpeakers)) {
            throw new InvalidArgumentException('known_speakers must be an array of speaker name => reference sample.');
        }
        if (count($knownSpeakers) > DiarizationBuilder::MAX_KNOWN_SPEAKERS) {
            throw new InvalidArgumentException(
                'A maximum of ' . DiarizationBuilder::MAX_KNOWN_SPEAKERS . ' known speakers is supported per diarization request.'
            );
        }

        $names = [];
        $references = [];
        foreach ($knownSpeakers as $name => $reference) {
            if (!is_string($reference) && !$reference instanceof SplFileInfo) {
                throw new InvalidArgumentException("Known speaker '{$name}' must reference an audio file path or a data URL.");
            }
            $names[] = (string)$name;
            $references[] = is_string($reference) && str_starts_with($reference, 'data:')
                ? $reference
                : MultipartFormData::toDataUrl($reference);
        }

        if ($names !== []) {
            $request['known_speaker_names'] = $names;
            $request['known_speaker_references'] = $references;
        }

        return $request;
    }

    /**
     * Validate that the audio file exists, is readable, and has a supported format.
     *
     * @param mixed $filePath
     * @throws FileValidationException If the file is invalid
     * @throws AudioTranscriptionException If the audio file validation fails
     * @phpstan-assert string $filePath
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

        $supportedFormats = ['flac', 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'ogg', 'wav', 'webm'];
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
