<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class EndpointRoutingException extends DomainException
{
    public function __construct(string $message = 'Endpoint routing configuration error.', int $code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        parent::__construct($message, $code);
    }

    public static function conflictingEndpoints(array $conflictingEndpoints, string $reasoning): self
    {
        $endpointsList = implode(', ', $conflictingEndpoints);

        return new self(
            "Conflicting endpoint configuration detected.\n\n" .
            "Reasoning:\n{$reasoning}\n\n" .
            "Conflicting endpoints: {$endpointsList}\n\n" .
            "Conclusion: Please resolve the conflict by disabling conflicting endpoints or adjusting the routing priority configuration.",
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public static function invalidPriorityConfiguration(string $reasoning): self
    {
        return new self(
            "Invalid endpoint priority configuration.\n\n" .
            "Reasoning:\n{$reasoning}\n\n" .
            "Conclusion: Please correct the routing.endpoint_priority configuration in your ai-assistant.php config file.",
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
