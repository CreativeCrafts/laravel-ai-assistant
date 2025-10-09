<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum Mode: string
{
    case TEXT = 'text';
    case CHAT = 'chat';
}
