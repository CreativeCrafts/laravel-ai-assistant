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
            if (!in_array($config['response_format'], $validResponseFormats, strict: true)) {
                throw new InvalidArgumentException(message: 'Invalid response format');
            }
        }

        return new TranscribeToData(
            model: $configData->string(key: 'model', default: Config::string(key: 'ai-assistant.audio_model'))->value(),
            temperature: $configData->float(key: 'temperature', default: is_numeric(Config::get('ai-assistant.temperature')) ? (float)Config::get('ai-assistant.temperature') : 0.0),
            responseFormat: $configData->string(key: 'response_format', default: 'json')->value(),
            filePath: $config['file'],
            language: $configData->string(key: 'language', default: 'en')->value(),
            prompt: $configData->string(key: 'prompt')->value(),
        );
    }

    /**
     * Builds and returns a CreateAssistantData object based on the provided configuration.
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
            topP: $configData->float(key: 'top_p', default: is_numeric(Config::get('ai-assistant.top_p')) ? (float)Config::get('ai-assistant.top_p') : 1.0),
            temperature: $configData->float(key: 'temperature', default: is_numeric(Config::get('ai-assistant.temperature')) ? (float)Config::get('ai-assistant.temperature') : 0.0),
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

        if (!isset($config['cacheConfig']) && Cache::has($cacheKey)) {
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

        $model = $configData->string(key: 'model', default: Config::string(key: 'ai-assistant.model'))->value();
        $temperature = $configData->float(
            key: 'temperature',
            default: Config::float(key: 'ai-assistant.temperature')
        );
        // comply with model constraints.
        if (str_starts_with($model, 'gpt-5') || str_starts_with($model, 'o3') || str_starts_with($model, 'o4')) {
            $temperature = 1.0;
        }

        return new ChatCompletionData(
            model: $configData->string(key: 'model', default: Config::string(key: 'ai-assistant.model'))->value(),
            message: $chatMessages,
            temperature: $temperature,
            store: $configData->boolean(key: 'store'),
            reasoningEffort: $configData->string(key: 'reasoning_effort', default: '')->value(),
            metadata: $configData->array(key: 'metadata'),
            maxCompletionTokens: $configData->integer(key: 'max_completion_tokens'),
            numberOfCompletionChoices: $configData->integer(key: 'n', default: 1),
            outputTypes: isset($config['modalities']) ? $configData->array(key: 'modalities') : null,
            audio: $configData->array(key: 'audio'),
            responseFormat: (new self())->buildResponseFormat($config),
            stopSequences: $configData->array(key: 'stop'),
            stream: $configData->boolean(key: 'stream'),
            streamOptions: $configData->array(key: 'stream_options'),
            topP: $configData->float(key: 'top_p', default: Config::float(key: 'ai-assistant.top_p'))
        );
    }

    /**
     * Builds and validates a response format configuration array for AI assistant requests.
     * This method processes the response_format configuration and converts it into a standardized
     * array format that can be used by the AI service. It handles both string and array formats
     * for response_format and performs validation to ensure the configuration is valid.
     *
     * @param array $config An associative array containing configuration options:
     *                      - 'response_format' (optional): Can be either a string ('json_schema', 'text', 'json_object', 'auto')
     *                        or an array with 'type' key and optional 'json_schema' configuration
     *                      - 'json_schema' (optional): Array containing JSON schema configuration when response_format is 'json_schema'
     *                        - 'name' (required): String name for the JSON schema
     *                        - 'schema' (optional): The actual JSON schema definition
     *                        - 'strict' (optional): Boolean indicating strict mode
     * @return array A standardized response format array containing:
     *               - 'type': The response format type
     *               - 'json_schema': Array with schema configuration (only when type is 'json_schema')
     *               Returns empty array if no valid response_format is provided
     * @throws InvalidArgumentException When response_format configuration is invalid or missing required fields
     */
    private function buildResponseFormat(array $config): array
    {
        if (isset($config['response_format']) && is_array($config['response_format'])) {
            $rf = $config['response_format'];

            if (!isset($rf['type']) || !is_string($rf['type'])) {
                throw new InvalidArgumentException('response_format array must include a string "type"');
            }

            if ($rf['type'] === 'json_schema') {
                if (!isset($rf['json_schema']) || !is_array($rf['json_schema'])) {
                    throw new InvalidArgumentException('response_format.json_schema must be an array when type is "json_schema"');
                }
                if (!isset($rf['json_schema']['name']) || !is_string($rf['json_schema']['name'])) {
                    throw new InvalidArgumentException('response_format.json_schema.name must be a string');
                }
            }

            return $rf;
        }

        $responseFormat = [];
        if (isset($config['response_format']) && is_string($config['response_format'])) {
            $validResponseFormats = ['json_schema', 'text', 'json_object', 'auto'];
            if (!in_array($config['response_format'], $validResponseFormats, true)) {
                throw new InvalidArgumentException('Invalid response format');
            }

            if ($config['response_format'] === 'json_object' || $config['response_format'] === 'text') {
                $responseFormat = [
                    'type' => fluent($config)->string(key: 'response_format')->value(),
                ];
            }

            if ($config['response_format'] === 'json_schema') {
                $jsonSchemaConfig = $config['json_schema'] ?? null;
                if (is_array($jsonSchemaConfig)) {
                    if (!isset($jsonSchemaConfig['name']) || !is_string($jsonSchemaConfig['name'])) {
                        throw new InvalidArgumentException('json_schema.name must be a string when response_format is "json_schema"');
                    }
                    $responseFormat = [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => $jsonSchemaConfig['name'],
                            ...(isset($jsonSchemaConfig['schema']) ? ['schema' => $jsonSchemaConfig['schema']] : []),
                            ...(isset($jsonSchemaConfig['strict']) ? ['strict' => (bool)$jsonSchemaConfig['strict']] : []),
                        ],
                    ];
                } else {
                    $name = fluent($config)->string(key: 'json_schema')->value();
                    $responseFormat = [
                        'type' => $name,
                        'json_schema' => ['name' => $name],
                    ];
                }
            }
        }

        return $responseFormat;
    }
}
