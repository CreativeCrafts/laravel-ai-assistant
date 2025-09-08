<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

readonly class AudioResource
{
    public function __construct(private OpenAITransport $transport)
    {
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function transcribe(array $parameters): TranscriptionResponse
    {
        // OpenAI Whisper transcription endpoint expects multipart/form-data with 'file' and 'model'
        $data = $this->transport->postMultipart('/v1/audio/transcriptions', $parameters, idempotent: true);

        $response = new TranscriptionResponse();
        $response->text = isset($data['text']) && is_string($data['text']) ? (string)$data['text'] : '';
        return $response;
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function translate(array $parameters): TranslationResponse
    {
        // OpenAI Whisper translation endpoint expects multipart/form-data with 'file' and 'model'
        $data = $this->transport->postMultipart('/v1/audio/translations', $parameters, idempotent: true);

        $response = new TranslationResponse();
        $response->text = isset($data['text']) && is_string($data['text']) ? (string)$data['text'] : '';
        return $response;
    }
}
