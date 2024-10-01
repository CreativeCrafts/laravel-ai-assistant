<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface TextEditCompletionContract
{
    public function __invoke(array $payload): string;
}
