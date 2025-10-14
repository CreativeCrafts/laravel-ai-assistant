<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

/**
 * Interface for audio-based endpoint adapters.
 *
 * This interface defines the contract for adapters that handle audio-based
 * requests and responses (e.g., audio transcription, translation, speech generation).
 * Adapters implementing this interface are restricted to audio processing only.
 *
 * @internal Used internally by ResponsesBuilder to transform audio requests for specific endpoints.
 * Do not use directly.
 */
interface AudioEndpointAdapter extends EndpointAdapter
{
}
