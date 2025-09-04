<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class MaxRetryAttemptsExceededException extends DomainException
{
    public function __construct(string $message = 'Maximum retry attempts exceeded for operation.', int $code = Response::HTTP_TOO_MANY_REQUESTS)
    {
        parent::__construct($message, $code);
    }
}
