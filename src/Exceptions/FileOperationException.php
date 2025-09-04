<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class FileOperationException extends DomainException
{
    public function __construct(string $message = 'File operation failed.', int $code = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        parent::__construct($message, $code);
    }
}
