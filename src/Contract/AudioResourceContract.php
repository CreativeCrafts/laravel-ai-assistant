<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface AudioResourceContract
{
    public function transcribeTo(array $payload): string;

    public function translateTo(array $payload): string;
}
