<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponseValidationException extends DomainException
{
    public function __construct(string $message = 'API response validation failed.', int $code = Response::HTTP_BAD_GATEWAY)
    {
        parent::__construct($message, $code);
    }
}
