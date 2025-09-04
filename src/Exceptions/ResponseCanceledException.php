<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;

final class ResponseCanceledException extends DomainException
{
    public function __construct(string $message = 'Response was canceled by the client.', int $code = 499)
    {
        parent::__construct($message, $code);
    }
}
