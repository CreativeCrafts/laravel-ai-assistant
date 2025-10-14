<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

/**
 * Interface for image-based endpoint adapters.
 *
 * This interface defines the contract for adapters that handle image-based
 * requests and responses (e.g., image generation, editing, variations).
 * Adapters implementing this interface are restricted to image processing only.
 *
 * @internal Used internally by ResponsesBuilder to transform image requests for specific endpoints.
 * Do not use directly.
 */
interface ImageEndpointAdapter extends EndpointAdapter
{
}
