<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface TextCompletionContract
{
    public function __invoke(array $payload): string;

    public function textCompletion(array $payload): string;

    public function streamedCompletion(array $payload): string;
}
