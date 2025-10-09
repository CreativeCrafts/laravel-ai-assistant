<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelConfig;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;

final class ArrayConfigAdapter
{
    /**
     * Convert a legacy array configuration to a typed ModelConfig.
     */
    public static function toModelConfig(array $config, Modality $modality): ModelConfig
    {
        $options = ModelOptions::fromArray($config);

        return ModelConfigFactory::for($modality, $options);
    }

    /**
     * Convert a ModelConfig to a legacy array format.
     */
    public static function toArray(ModelConfig $config): array
    {
        return $config->toArray();
    }

    /**
     * Merge legacy array config with ModelConfig, returning a new array.
     * This allows partial overrides of typed configs with array-based values.
     */
    public static function merge(ModelConfig $config, array $overrides): array
    {
        return array_merge($config->toArray(), $overrides);
    }

    /**
     * Create a ModelConfig from modality string and array options.
     * Useful when working with string-based modality identifiers.
     */
    public static function fromModalityString(string $modalityString, array $options): ModelConfig
    {
        $modality = Modality::from($modalityString);
        $modelOptions = ModelOptions::fromArray($options);

        return ModelConfigFactory::for($modality, $modelOptions);
    }
}
