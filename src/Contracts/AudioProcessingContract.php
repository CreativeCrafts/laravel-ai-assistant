<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Contract for audio processing operations.
 *
 * This interface defines methods for audio transcription and translation
 * using the OpenAI API.
 */
interface AudioProcessingContract
{
    /**
     * Transcribe audio to text using the OpenAI API.
     *
     * This function sends an audio file to the OpenAI API for transcription
     * and returns the transcribed text.
     *
     * @param array $payload An array containing the necessary information for transcription.
     *                       This typically includes:
     *                       - 'file': The audio file to be transcribed (required)
     *                       - 'model': The model to use for transcription (optional)
     *                       - 'prompt': An optional text to guide the model's style or continue a previous audio segment
     *                       - 'response_format': The format of the transcript output (optional)
     *                       - 'temperature': The sampling temperature to use (optional)
     *                       - 'language': The language of the input audio (optional)
     *
     * @return string The transcribed text from the audio file.
     */
    public function transcribeTo(array $payload): string;

    /**
     * Translate audio to text using the OpenAI API.
     *
     * This function sends an audio file to the OpenAI API for translation
     * and returns the translated text.
     *
     * @param array $payload An array containing the necessary information for translation.
     *                       This typically includes:
     *                       - 'file': The audio file to be translated (required)
     *                       - 'model': The model to use for translation (optional)
     *                       - 'prompt': An optional text to guide the model's style or continue a previous audio segment
     *                       - 'response_format': The format of the transcript output (optional)
     *                       - 'temperature': The sampling temperature to use (optional)
     *
     * @return string The translated text from the audio file.
     */
    public function translateTo(array $payload): string;
}
