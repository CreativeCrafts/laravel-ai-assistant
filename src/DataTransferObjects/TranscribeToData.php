<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\TranscribeToDataContract;

final readonly class TranscribeToData implements TranscribeToDataContract
{
    public function __construct(
        protected string $model,
        protected float $temperature,
        protected string $responseFormat,
        protected mixed $filePath,
        protected string $language,
        protected ?string $prompt = null
    ) {
    }

    /**
     * Convert the TranscribeToData object to an array.
     *
     * This method transforms the object's properties into an associative array,
     * which can be used for API requests or other data processing needs.
     *
     * @return array An associative array containing the object's properties:
     *               - 'model': The AI model to be used for transcription.
     *               - 'temperature': The sampling temperature for the AI model.
     *               - 'response_format': The desired format of the transcription response.
     *               - 'file': The path to the audio file to be transcribed.
     *               - 'language': The language of the audio content.
     *               - 'prompt': (Optional) A prompt to guide the transcription, if provided.
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'model' => $this->model,
                'temperature' => $this->temperature,
                'response_format' => $this->responseFormat,
                'file' => $this->filePath,
                'language' => $this->language,
            ],
            $this->prompt !== null ? [
                'prompt' => $this->prompt,
            ] : []
        );
    }
}
