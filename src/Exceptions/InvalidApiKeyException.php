<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidApiKeyException extends InvalidArgumentException
{
    public function __construct(string $message = 'Missing API key. Set OPENAI_API_KEY or ai-assistant.api_key. See config/ai-assistant.php.', int $code = Response::HTTP_UNAUTHORIZED)
    {
        parent::__construct($message, $code);
    }
}
