<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum ProgressStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Canceled = 'canceled';
    case Done = 'done';
    case Error = 'error';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Canceled, self::Done, self::Error]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Queued, self::Running]);
    }
}
