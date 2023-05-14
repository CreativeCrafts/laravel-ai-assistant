<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface TextEditCompletionContract
{
    public function __invoke(array $payload): string;
}
