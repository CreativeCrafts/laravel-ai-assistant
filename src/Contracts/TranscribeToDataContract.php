<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface TranscribeToDataContract
{
    public function __construct(
        string $model,
        float $temperature,
        string $responseFormat,
        mixed $filePath,
        string $language,
        ?string $prompt = null
    );

    public function toArray(): array;
}
