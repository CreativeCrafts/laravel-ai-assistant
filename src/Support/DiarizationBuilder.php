<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\Contracts\AudioRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedSegment;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedTranscription;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranscriptionException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use Generator;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Fluent builder for speaker diarization: transcribe a conversation and identify who spoke when.
 *
 * Uses OpenAI's gpt-4o-transcribe-diarize model with the diarized_json response format. Speakers
 * are labelled A, B, C, ... unless known speakers are supplied with short reference samples, in
 * which case segments are labelled with the given names.
 *
 * ```php
 * $result = Ai::diarize(storage_path('calls/support.mp3'))
 *     ->knownSpeaker('agent', storage_path('voices/agent.wav'))
 *     ->knownSpeaker('customer', storage_path('voices/customer.wav'))
 *     ->send();
 *
 * $result->speakers();          // ['agent', 'customer']
 * $result->textFor('customer'); // everything the customer said
 * $result->toTranscript();      // "agent: Thanks for calling..."
 * ```
 *
 * This builder follows the mutable fluent pattern used by the other package builders.
 */
final class DiarizationBuilder
{
    public const DEFAULT_MODEL = 'gpt-4o-transcribe-diarize';
    public const RESPONSE_FORMAT = 'diarized_json';
    public const MAX_KNOWN_SPEAKERS = 4;
    public const SUPPORTED_FORMATS = ['flac', 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'ogg', 'wav', 'webm'];

    private mixed $file = null;
    private string $model = self::DEFAULT_MODEL;
    private ?string $language = null;
    private ?float $temperature = null;
    /** @var string|array<string, mixed> */
    private string|array $chunkingStrategy = 'auto';
    /** @var array<string, string> Speaker name => data URL of the reference sample */
    private array $knownSpeakers = [];
    /** @var array<string, mixed> */
    private array $options = [];

    public function __construct(
        private readonly AudioRepositoryContract $audio,
    ) {
    }

    /**
     * The conversation recording to analyse: a local path, an SplFileInfo (including uploaded files), or
     * an open stream resource (pass $filename for streams so the API can detect the audio format).
     *
     * The API detects the audio format from the filename it receives, so uploaded files are sent under
     * their original client filename and the format is validated against that name.
     *
     * @param string|SplFileInfo|resource $file
     */
    public function file(mixed $file, ?string $filename = null): self
    {
        if (is_string($file) || $file instanceof SplFileInfo) {
            $path = $file instanceof SplFileInfo ? ($file->getRealPath() ?: $file->getPathname()) : $file;
            // Uploads are stored at extension-less temporary paths such as /tmp/phpAbC123
            if ($filename === null && $file instanceof UploadedFile) {
                $filename = $file->getClientOriginalName();
            }
            $this->validateAudioFile($path, $filename ?? $path);
            $this->file = $filename === null
                ? $path
                : ['contents' => $path, 'filename' => $filename, 'content_type' => MultipartFormData::mimeTypeFromExtension($filename)];

            return $this;
        }

        if (!is_resource($file)) {
            throw FileValidationException::invalidPathType($file);
        }

        $this->file = $filename === null
            ? $file
            : ['contents' => $file, 'filename' => $filename, 'content_type' => MultipartFormData::mimeTypeFromExtension($filename)];

        return $this;
    }

    /**
     * Read the recording from a Laravel filesystem disk (e.g. S3).
     */
    public function fromDisk(string $disk, string $path): self
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (!is_resource($stream)) {
            throw FileValidationException::fileNotFound("{$disk}://{$path}");
        }

        return $this->file($stream, basename($path));
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * ISO-639-1 language of the audio (e.g. 'en'); improves accuracy and latency.
     */
    public function language(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function temperature(float $temperature): self
    {
        if ($temperature < 0 || $temperature > 1) {
            throw new InvalidArgumentException('Temperature must be between 0 and 1');
        }
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Let the API pick chunk boundaries (loudness normalisation + voice activity detection).
     * This is the default and is required for recordings longer than 30 seconds.
     */
    public function autoChunking(): self
    {
        $this->chunkingStrategy = 'auto';

        return $this;
    }

    /**
     * Tune server-side voice activity detection used to cut the audio into chunks.
     */
    public function serverVad(?float $threshold = null, ?int $prefixPaddingMs = null, ?int $silenceDurationMs = null): self
    {
        if ($threshold !== null && ($threshold < 0 || $threshold > 1)) {
            throw new InvalidArgumentException('VAD threshold must be between 0 and 1');
        }

        $this->chunkingStrategy = array_filter([
            'type' => 'server_vad',
            'threshold' => $threshold,
            'prefix_padding_ms' => $prefixPaddingMs,
            'silence_duration_ms' => $silenceDurationMs,
        ], static fn (mixed $value): bool => $value !== null);

        return $this;
    }

    /**
     * Identify a known voice by name. The reference must be a 2-10 second sample of that speaker,
     * given as a local file path, an SplFileInfo or a data URL ("data:audio/wav;base64,...").
     * Up to four known speakers are supported.
     */
    public function knownSpeaker(string $name, string|SplFileInfo $reference): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Known speaker name must be a non-empty string.');
        }

        if (!isset($this->knownSpeakers[$name]) && count($this->knownSpeakers) >= self::MAX_KNOWN_SPEAKERS) {
            throw new InvalidArgumentException(
                'A maximum of ' . self::MAX_KNOWN_SPEAKERS . ' known speakers is supported per diarization request.'
            );
        }

        $this->knownSpeakers[$name] = is_string($reference) && str_starts_with($reference, 'data:')
            ? $reference
            : MultipartFormData::toDataUrl($reference);

        return $this;
    }

    /**
     * @param array<string, string|SplFileInfo> $speakers Speaker name => reference sample
     */
    public function knownSpeakers(array $speakers): self
    {
        foreach ($speakers as $name => $reference) {
            $this->knownSpeaker((string)$name, $reference);
        }

        return $this;
    }

    /**
     * Pass additional transcription parameters straight through to the API.
     *
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * The transcription request payload sent to POST /v1/audio/transcriptions.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        if ($this->file === null) {
            throw AudioTranscriptionException::missingFile();
        }

        $payload = array_merge($this->options, [
            'file' => $this->file,
            'model' => $this->model,
            'response_format' => self::RESPONSE_FORMAT,
            'chunking_strategy' => $this->chunkingStrategy,
        ]);

        if ($this->language !== null) {
            $payload['language'] = $this->language;
        }
        if ($this->temperature !== null) {
            $payload['temperature'] = $this->temperature;
        }
        if ($this->knownSpeakers !== []) {
            $payload['known_speaker_names'] = array_keys($this->knownSpeakers);
            $payload['known_speaker_references'] = array_values($this->knownSpeakers);
        }

        return $payload;
    }

    /**
     * Transcribe the recording and return the speaker-annotated result.
     */
    public function send(): DiarizedTranscription
    {
        return DiarizedTranscription::fromArray($this->audio->createTranscription($this->toPayload()));
    }

    /**
     * Stream the diarization. Each speaker segment is yielded as soon as the API finalises it;
     * the generator's return value (getReturn()) is the complete DiarizedTranscription.
     *
     * @param (callable(string, string|null): void)|null $onDelta Receives partial text and its segment id
     * @return Generator<int, DiarizedSegment, mixed, DiarizedTranscription>
     */
    public function stream(?callable $onDelta = null): Generator
    {
        $segments = [];
        $text = null;
        $usage = [];

        foreach ($this->audio->streamTranscription($this->toPayload()) as $event) {
            $type = $event['type'] ?? null;

            if ($type === 'transcript.text.segment') {
                $segment = DiarizedSegment::fromArray($event);
                $segments[] = $segment;
                yield $segment;
            } elseif ($type === 'transcript.text.delta') {
                $delta = $event['delta'] ?? null;
                $segmentId = $event['segment_id'] ?? null;
                if ($onDelta !== null && is_string($delta)) {
                    $onDelta($delta, is_string($segmentId) ? $segmentId : null);
                }
            } elseif ($type === 'transcript.text.done') {
                $text = is_string($event['text'] ?? null) ? $event['text'] : null;
                $usage = is_array($event['usage'] ?? null) ? $event['usage'] : [];
            } elseif ($type === 'error') {
                $error = $event['error'] ?? $event;
                $message = is_array($error) && is_string($error['message'] ?? null) ? $error['message'] : 'Unknown streaming error';
                throw new AudioTranscriptionException(
                    message: "Diarization stream failed: {$message}",
                    reason: $message,
                    model: $this->model
                );
            }
        }

        return new DiarizedTranscription(
            text: $text !== null ? trim($text) : implode(' ', array_map(static fn (DiarizedSegment $s): string => $s->text, $segments)),
            segments: $segments,
            duration: null,
            usage: $usage,
            raw: [],
        );
    }

    /**
     * @param string $name The filename the API receives, which determines the audio format
     */
    private function validateAudioFile(string $path, string $name): void
    {
        if (!file_exists($path)) {
            throw FileValidationException::fileNotFound($path);
        }
        if (!is_readable($path)) {
            throw FileValidationException::fileNotReadable($path);
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, self::SUPPORTED_FORMATS, true)) {
            throw FileValidationException::unsupportedFormat($path, $extension, self::SUPPORTED_FORMATS);
        }

        $limitMb = config('ai-assistant.audio.file_size_limit_mb', 25);
        $maxBytes = (int)round((is_numeric($limitMb) ? (float)$limitMb : 25.0) * 1024 * 1024);
        $size = filesize($path);
        if ($size !== false && $maxBytes > 0 && $size > $maxBytes) {
            throw FileValidationException::fileSizeExceeded($path, $size, $maxBytes);
        }
    }
}
