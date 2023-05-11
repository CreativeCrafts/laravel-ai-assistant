<?php

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidApiKeyException extends DomainException
{
    public function __construct(string $message = 'Invalid OpenAI API key or organization.', int $code = Response::HTTP_NOT_ACCEPTABLE)
    {
        parent::__construct($message, $code);
    }
}
