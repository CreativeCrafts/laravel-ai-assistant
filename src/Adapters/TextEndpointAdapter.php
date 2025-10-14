<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

/**
 * Interface for text-based endpoint adapters.
 *
 * This interface defines the contract for adapters that handle text-based
 * requests and responses (e.g., chat completion, response API).
 * Adapters implementing this interface are restricted to text processing only.
 *
 * @internal Used internally by ResponsesBuilder to transform text requests for specific endpoints.
 * Do not use directly.
 */
interface TextEndpointAdapter extends EndpointAdapter
{
}
