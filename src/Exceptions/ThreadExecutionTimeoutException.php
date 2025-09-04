<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class ThreadExecutionTimeoutException extends DomainException
{
    public function __construct(string $message = 'Thread execution exceeded maximum timeout period.', int $code = Response::HTTP_REQUEST_TIMEOUT)
    {
        parent::__construct($message, $code);
    }
}
