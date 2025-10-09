<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum Transport: string
{
    case SYNC = 'sync';
    case STREAM = 'stream';
}
