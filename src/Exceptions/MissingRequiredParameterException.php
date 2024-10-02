<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class MissingRequiredParameterException extends DomainException
{
    public function __construct(string $message = 'Missing required parameter.', int $code = Response::HTTP_NOT_ACCEPTABLE)
    {
        parent::__construct($message, $code);
    }
}
