<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;

final readonly class ToolsBuilder
{
    public function __construct(private AiAssistant $core)
    {
    }

    public function includeFunctionCallTool(string $functionName, string $functionDescription, array|FunctionCallParameterContract $functionParameters, bool $isStrict = false): self
    {
        if ($functionParameters instanceof FunctionCallParameterContract) {
            $this->core->includeFunctionCallToolFromContract($functionName, $functionDescription, $functionParameters, $isStrict);
        } else {
            $this->core->includeFunctionCallTool($functionName, $functionDescription, $functionParameters, $isStrict);
        }
        return $this;
    }

    public function includeFunctionFromCallable(callable $fn, ?string $exportedName = null, string $description = '', bool $isStrict = false): self
    {
        $this->core->includeFunctionFromCallable($fn, $exportedName, $description, $isStrict);
        return $this;
    }

    public function includeFileSearchTool(array $vectorStoreIds = []): self
    {
        $this->core->includeFileSearchTool($vectorStoreIds);
        return $this;
    }

    public function includeCodeInterpreterTool(array $fileIds = []): self
    {
        $this->core->includeCodeInterpreterTool($fileIds);
        return $this;
    }

    public function setToolChoice(string|array $choice): self
    {
        $this->core->setToolChoice($choice);
        return $this;
    }

    public function useFileSearch(bool $enabled = true): self
    {
        $this->core->useFileSearch($enabled);
        return $this;
    }
}
