<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CreateNewAssistantException extends DomainException
{
    public function __construct(string $message = 'Unable to create new assistant.', int $code = Response::HTTP_NOT_ACCEPTABLE)
    {
        parent::__construct($message, $code);
    }
}
