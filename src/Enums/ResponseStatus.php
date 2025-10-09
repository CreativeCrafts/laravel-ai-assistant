<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum ResponseStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case RequiresAction = 'requires_action';
    case Unknown = 'unknown';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::InProgress, self::RequiresAction]);
    }

    public function requiresIntervention(): bool
    {
        return $this === self::RequiresAction;
    }
}
