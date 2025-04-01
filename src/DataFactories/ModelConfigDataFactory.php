<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataFactories;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionDataContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\CreateAssistantDataContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ModelConfigDataFactoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\TranscribeToDataContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatCompletionData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CreateAssistantData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\TranscribeToData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

final class ModelConfigDataFactory implements ModelConfigDataFactoryContract
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
    public static function buildTranscribeData(array $config): TranscribeToDataContract
    {
        $configData = fluent($config);

        if (isset($config['response_format'])) {
            $validResponseFormats = ['json', 'text', 'srt', 'vtt', 'verbose_json'];
            if (is_array($config['response_format'])) {
                throw new InvalidArgumentException(message: 'Response format must be a string');
            }
            if (! in_array($config['response_format'], $validResponseFormats, strict: true)) {
                throw new InvalidArgumentException(message: 'Invalid response format');
            }
        }

        return new TranscribeToData(
            model: $configData->string(key: 'model', default: Config::string(key: 'ai-assistant.audio_model'))->value(),
            temperature: $configData->float(key: 'temperature', default: Config::float(key: 'ai-assistant.temperature')),
            responseFormat: $configData->string(key: 'response_format', default: 'json')->value(),
            filePath: $config['file'],
            language: $configData->string(key: 'language', default: 'en')->value(),
            prompt: $configData->string(key: 'prompt')->value(),
        );
    }

    /**
     * Builds and returns a CreateAssistantData object based on the provided configuration.
     *
     * This method creates a CreateAssistantData object using the given configuration array.
     * It sets default values from the application's configuration if certain keys are not provided.
     *
     * @param array $config An associative array containing configuration options:
     *                      - 'model' (optional): The AI model to use for the assistant.
     *                      - 'top_p' (optional): The top p sampling parameter.
     *                      - 'temperature' (optional): The sampling temperature to use.
     *                      - 'description' (required): A description of the assistant.
     *                      - 'name' (required): The name of the assistant.
     *                      - 'instructions' (required): Instructions for the assistant.
     *                      - 'reasoning_effort' (required): The level of reasoning effort for the assistant.
     *                      - 'tools' (optional): An array of tools available to the assistant.
     *                      - 'tool_resources' (optional): An array of resources for the tools.
     *                      - 'metadata' (optional): Additional metadata for the assistant.
     *                      - 'response_format' (optional): The desired format of the response.
     *
     * @return CreateAssistantDataContract A CreateAssistantData object containing the configured assistant parameters.
     */
    public static function buildCreateAssistantData(array $config): CreateAssistantDataContract
    {
        $configData = fluent($config);
        $responseFormat = (new self())->buildResponseFormat($config);
        if ($responseFormat === []) {
            $responseFormat = 'auto';
        }
        return new CreateAssistantData(
            model: $configData->string(key: 'model', default: Config::string(key: 'ai-assistant.model'))->value(),
            topP: $configData->float(key: 'top_p', default: Config::integer(key: 'ai-assistant.top_p')),
            temperature: $configData->float(key: 'temperature', default: Config::float(key: 'ai-assistant.temperature')),
            assistantDescription: $configData->string(key: 'description')->value(),
            assistantName: $configData->string(key: 'name')->value(),
            instructions: $configData->string(key: 'instructions')->value(),
            reasoningEffort: $configData->string(key: 'reasoning_effort')->value(),
            tools: $configData->array(key: 'tools'),
            toolResources: $configData->array(key: 'tool_resources'),
            metadata: $configData->array(key: 'metadata'),
            responseFormat: $responseFormat,
        );
    }

    public static function buildChatCompletionData(array $config): ChatCompletionDataContract
    {
        $configData = fluent($config);
        $cacheKey = $configData->string(key: 'cacheConfig.key')->value();

        if (! isset($config['cacheConfig']) && Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
        }

        $chatMessages = $config['messages'];
        if (isset($config['cacheConfig'])) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $chatMessages = array_merge([$cached], $chatMessages);
            }
            Cache::put(
                $cacheKey,
                $chatMessages,
                $configData->integer(key: 'cacheConfig.ttl', default: 60)
            );
        }

        return new ChatCompletionData(
            model: $configData->string(key: 'model', default: Config::string(key: 'ai-assistant.model'))->value(),
            message: $chatMessages,
            temperature: $configData->float(key: 'temperature', default: Config::float(key: 'ai-assistant.temperature')),
            store: $configData->boolean(key: 'store'),
            reasoningEffort: $configData->string(key: 'reasoning_effort', default: '')->value(),
            metadata: $configData->array(key: 'metadata'),
            maxCompletionTokens: $configData->integer(key: 'max_completion_tokens'),
            numberOfCompletionChoices: $configData->integer(key: 'n', default: 1),
            outputTypes: $configData->array(key: 'modalities'),
            audio: $configData->array(key: 'audio'),
            responseFormat: (new self())->buildResponseFormat($config),
            stopSequences: $configData->array(key: 'stop'),
            stream: $configData->boolean(key: 'stream'),
            streamOptions: $configData->array(key: 'stream_options'),
            topP: $configData->float(key: 'top_p', default: Config::integer(key: 'ai-assistant.top_p'))
        );
    }

    /**
     * Constructs the response format configuration array based on the provided configuration.
     *
     * This private method validates and builds the response format array using the given configuration options.
     * It checks if the "response_format" key is set and is a string, then validates it against the allowed values:
     * "json_schema", "text", "json_object", and "auto". If an invalid format is provided, the method throws an
     * InvalidArgumentException.
     *
     * The method handles the response format as follows:
     * - For "json_object" or "text": Returns an array with a single "type" key set to the value of "response_format".
     * - For "json_schema": Expects an additional "json_schema" key in the configuration and returns an array with:
     *     - "type" set to the value from the "json_schema" key.
     *     - "json_schema" as an array containing a "name" key, also set to the value from the "json_schema" key.
     * - For "auto" or if "response_format" is not provided as a valid string, an empty array is returned.
     *
     * @param array $config The configuration array which may contain:
     *                      - "response_format" (string, optional): One of "json_schema", "text", "json_object", or "auto".
     *                      - "json_schema" (string, required if "response_format" is "json_schema"): The schema name to use.
     *
     * @return array The constructed response format configuration array.
     *
     * @throws InvalidArgumentException If the provided "response_format" is not one of the allowed values.
     */
    private function buildResponseFormat(array $config): array
    {
        $responseFormat = [];
        if (isset($config['response_format']) && is_string($config['response_format'])) {
            $validResponseFormats = ['json_schema', 'text', 'json_object', 'auto'];
            if (! in_array($config['response_format'], $validResponseFormats, strict: true)) {
                throw new InvalidArgumentException(message: 'Invalid response format');
            }
            if ($config['response_format'] === 'json_object' || $config['response_format'] === 'text') {
                $responseFormat = [
                    'type' => fluent($config)->string(key: 'response_format')->value(),
                ];
            }

            if ($config['response_format'] === 'json_schema') {
                $responseFormat = [
                    'type' => fluent($config)->string(key: 'json_schema')->value(),
                    'json_schema' => [
                        'name' => fluent($config)->string(key: 'json_schema')->value(),
                    ],
                ];
            }
        }
        return $responseFormat;
    }
}
