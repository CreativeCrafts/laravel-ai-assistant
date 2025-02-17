<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface CreateAssistantDataContract
{
    public function __construct(
        string $model,
        float|int|null $topP = null,
        ?float $temperature = null,
        ?string $assistantDescription = null,
        ?string $assistantName = null,
        ?string $instructions = null,
        ?string $reasoningEffort = null,
        ?array $tools = null,
        ?array $toolResources = null,
        ?array $metadata = null,
        string|array $responseFormat = 'auto',
    );

    public function toArray(): array;
}
