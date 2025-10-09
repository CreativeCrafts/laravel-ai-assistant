<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Factories;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelConfig;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

final class ModelConfigFactory
{
    public static function for(Modality $modality, ModelOptions $options): ModelConfig
    {
        $normalizedStop = self::normalizeStop($options->stop);

        return match ($modality) {
            Modality::Text => new ModelConfig(
                modality: Modality::Text,
                model: $options->model ?? self::getStringConfig('ai-assistant.model'),
                maxTokens: $options->maxTokens ?? self::getIntConfig('ai-assistant.max_completion_tokens'),
                temperature: $options->temperature,
                stream: $options->stream,
                echo: $options->echo,
                n: $options->n,
                suffix: $options->suffix,
                topP: $options->topP,
                presencePenalty: $options->presencePenalty,
                frequencyPenalty: $options->frequencyPenalty,
                bestOf: $options->bestOf,
                stop: $normalizedStop,
            ),

            Modality::Chat => new ModelConfig(
                modality: Modality::Chat,
                model: $options->model ?? self::getStringConfig('ai-assistant.chat_model'),
                maxTokens: $options->maxTokens ?? self::getIntConfig('ai-assistant.max_completion_tokens'),
                temperature: $options->temperature,
                stream: $options->stream,
                n: $options->n,
                topP: $options->topP,
                presencePenalty: $options->presencePenalty,
                frequencyPenalty: $options->frequencyPenalty,
                stop: $normalizedStop,
            ),

            Modality::Edit => new ModelConfig(
                modality: Modality::Edit,
                model: $options->model ?? self::getStringConfig('ai-assistant.edit_model'),
                temperature: $options->temperature,
                topP: $options->topP,
            ),

            Modality::AudioToText => new ModelConfig(
                modality: Modality::AudioToText,
                model: $options->model ?? self::getStringConfig('ai-assistant.audio_model'),
                temperature: $options->temperature,
                responseFormat: $options->responseFormat ?? self::getStringConfig('ai-assistant.response_format'),
            ),

            Modality::Image => new ModelConfig(
                modality: Modality::Image,
                model: $options->model ?? self::getStringConfig('ai-assistant.image_model'),
                n: $options->n,
                responseFormat: $options->responseFormat,
            ),
        };
    }

    /**
     * Normalize the 'stop' parameter to null|string|array of strings as expected by the API.
     * Accepts string, array, or null; trims strings and filters out empty values.
     */
    public static function normalizeStop(mixed $stop): array|string|null
    {
        if ($stop === null || $stop === '') {
            return null;
        }
        if (is_string($stop)) {
            $trimmed = trim($stop);
            return $trimmed === '' ? null : $trimmed;
        }
        if (is_array($stop)) {
            $normalized = [];
            foreach ($stop as $item) {
                if (is_string($item)) {
                    $t = trim($item);
                    if ($t !== '') {
                        $normalized[] = $t;
                    }
                }
            }
            return $normalized === [] ? null : $normalized;
        }
        return null;
    }

    private static function getStringConfig(string $key): ?string
    {
        $value = config($key);
        return is_string($value) ? $value : null;
    }

    private static function getIntConfig(string $key): ?int
    {
        $value = config($key);
        return is_numeric($value) ? (int)$value : null;
    }
}
