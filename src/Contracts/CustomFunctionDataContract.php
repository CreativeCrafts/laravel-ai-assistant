<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface CustomFunctionDataContract
{
    public function toArray(): array;
}
