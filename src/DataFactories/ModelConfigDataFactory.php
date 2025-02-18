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
        return new TranscribeToData(
            model: fluent($config)->string(key: 'model', default: Config::string(key: 'ai-assistant.audio_model'))->toString(),
            temperature: fluent($config)->float(key: 'temperature', default: Config::float(key: 'ai-assistant.temperature')),
            responseFormat: $config['response_format'] ?? 'auto',
            filePath: $config['file'],
            language: fluent($config)->string(key: 'language', default: 'en')->toString(),
            prompt: fluent($config)->string(key: 'prompt')->toString(),
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
        return new CreateAssistantData(
            model: fluent($config)->string(key: 'model', default: Config::string(key: 'ai-assistant.model'))->toString(),
            topP: fluent($config)->float(key: 'top_p', default: Config::integer(key: 'ai-assistant.top_p')),
            temperature: fluent($config)->float(key: 'temperature', default: Config::float(key: 'ai-assistant.temperature')),
            assistantDescription: fluent($config)->string(key: 'description')->toString(),
            assistantName: fluent($config)->string(key: 'name')->toString(),
            instructions: fluent($config)->string(key: 'instructions')->toString(),
            reasoningEffort: fluent($config)->string(key: 'reasoning_effort')->toString(),
            tools: fluent($config)->array(key: 'tools'),
            toolResources: fluent($config)->array(key: 'tool_resources'),
            metadata: fluent($config)->array(key: 'metadata'),
            responseFormat: $config['response_format'] ?? 'auto',
        );
    }

    public static function buildChatCompletionData(array $config): ChatCompletionDataContract
    {
        $cacheKey = fluent($config)->string(key: 'cacheConfig.key')->toString();

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
                fluent($config)->integer(key: 'cacheConfig.ttl', default: 60)
            );
        }

        return new ChatCompletionData(
            model: fluent($config)->string(key: 'model', default: Config::string(key: 'ai-assistant.model'))->toString(),
            message: $chatMessages,
            temperature: fluent($config)->float(key: 'temperature', default: Config::float(key: 'ai-assistant.temperature')),
            store: fluent($config)->boolean(key: 'store'),
            reasoningEffort: fluent($config)->string(key: 'reasoning_effort', default: '')->toString(),
            metadata: fluent($config)->array(key: 'metadata'),
            maxCompletionTokens: fluent($config)->integer(key: 'max_completion_tokens'),
            numberOfCompletionChoices: fluent($config)->integer(key: 'n', default: 1),
            outputTypes: fluent($config)->array(key: 'modalities'),
            audio: fluent($config)->array(key: 'audio'),
            responseFormat: fluent($config)->array(key: 'response_format'),
            stopSequences: fluent($config)->array(key: 'stop'),
            stream: fluent($config)->boolean(key: 'stream'),
            streamOptions: fluent($config)->array(key: 'stream_options'),
            topP: fluent($config)->float(key: 'top_p', default: Config::integer(key: 'ai-assistant.top_p'))
        );
    }
}
