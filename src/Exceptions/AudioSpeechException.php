<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when audio speech generation fails.
 *
 * Includes text input and failure reason to help developers
 * quickly identify and resolve speech generation issues.
 */
final class AudioSpeechException extends RuntimeException
{
    private ?string $text;
    private ?string $reason;
    private ?string $model;
    private ?string $voice;

    public function __construct(
        string $message,
        ?string $text = null,
        ?string $reason = null,
        ?string $model = null,
        ?string $voice = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->text = $text;
        $this->reason = $reason;
        $this->model = $model;
        $this->voice = $voice;
    }

    /**
     * Create exception for missing text input.
     */
    public static function missingText(): self
    {
        return new self(
            message: 'Text input is required for speech generation',
            reason: 'No text provided in the request'
        );
    }

    /**
     * Create exception for speech generation failure.
     */
    public static function generationFailed(string $text, string $reason, ?string $model = null, ?string $voice = null): self
    {
        $textPreview = mb_strlen($text) > 50 ? mb_substr($text, 0, 50) . '...' : $text;
        $message = "Audio speech generation failed for text: '{$textPreview}'. Reason: {$reason}";
        if ($model !== null) {
            $message .= " (Model: {$model})";
        }
        if ($voice !== null) {
            $message .= " (Voice: {$voice})";
        }

        return new self(
            message: $message,
            text: $text,
            reason: $reason,
            model: $model,
            voice: $voice
        );
    }

    /**
     * Create exception for text that's too long.
     */
    public static function textTooLong(string $text, int $maxLength = 4096): self
    {
        $length = mb_strlen($text);
        $textPreview = mb_substr($text, 0, 50) . '...';

        return new self(
            message: "Text length ({$length} characters) exceeds maximum allowed length ({$maxLength} characters). Consider breaking the text into smaller chunks.",
            text: $text,
            reason: "Text length exceeds OpenAI limit of {$maxLength} characters"
        );
    }

    /**
     * Create exception for invalid voice selection.
     */
    public static function invalidVoice(string $voice): self
    {
        $supportedVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
        $supported = implode(', ', $supportedVoices);

        return new self(
            message: "Invalid voice: {$voice}. Supported voices: {$supported}",
            reason: "Voice '{$voice}' is not supported",
            voice: $voice
        );
    }

    /**
     * Create exception for invalid speed value.
     */
    public static function invalidSpeed(float $speed): self
    {
        return new self(
            message: "Invalid speed value: {$speed}. Speed must be between 0.25 and 4.0.",
            reason: "Speed value {$speed} is outside the valid range (0.25-4.0)"
        );
    }

    /**
     * Create exception for empty text input.
     */
    public static function emptyText(): self
    {
        return new self(
            message: 'Text input cannot be empty for speech generation',
            reason: 'Empty text provided in the request'
        );
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getVoice(): ?string
    {
        return $this->voice;
    }
}
