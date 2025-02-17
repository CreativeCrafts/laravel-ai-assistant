<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ModelConfigDataFactoryContract
{
    /**
     * Builds and returns a TranscribeToData object based on the provided configuration.
     *
     * This method creates a TranscribeToData object using the given configuration array.
     * It sets default values from the application's configuration if certain keys are not provided.
     *
     * @param array $config An associative array containing configuration options:
     *                      - 'model' (optional): The AI model to use for transcription.
     *                      - 'temperature' (optional): The sampling temperature to use.
     *                      - 'response_format' (optional): The desired format of the response.
     *                      - 'file' (required): The path to the audio file to be transcribed.
     *                      - 'language' (required): The language of the audio file.
     *                      - 'prompt' (optional): A prompt to guide the transcription.
     *
     * @return TranscribeToDataContract A TranscribeToData object containing the configured transcription parameters.
     */
    public static function buildTranscribeData(array $config): TranscribeToDataContract;
}
