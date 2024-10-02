<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

use CreativeCrafts\LaravelAiAssistant\Assistant;
use CreativeCrafts\LaravelAiAssistant\Tasks\AssistantResource;

interface AssistantContract
{
    public static function new(): Assistant;

    public function client(AssistantResource $client): Assistant;

    public function setModelName(string $modelName): Assistant;

    public function adjustTemperature(int|float $temperature): Assistant;

    public function setAssistantName(string $assistantName = ''): Assistant;

    public function setAssistantDescription(string $assistantDescription = ''): Assistant;

    public function setInstructions(string $instructions = ''): Assistant;

    public function includeCodeInterpreterTool(array $fileIds = []): Assistant;

    public function includeFileSearchTool(array $vectorStoreIds = []): Assistant;

    public function includeFunctionCallTool(
        string $functionName,
        string $functionDescription = '',
        FunctionCallParameterContract|array $functionParameters = [],
        bool $isStrict = false,
        array $requiredParameters = [],
        bool $hasAdditionalProperties = false
    ): Assistant;

    public function create(): NewAssistantResponseDataContract;

    public function assignAssistant(string $assistantId): Assistant;

    public function createTaskThread(array $parameters = []): Assistant;

    public function askQuestion(string $message): Assistant;

    public function process(): Assistant;

    public function get(): string;
}
