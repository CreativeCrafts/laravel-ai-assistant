<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Events;

/**
 * Dispatched for every verified webhook event, e.g. response.completed, batch.completed,
 * fine_tuning.job.succeeded, eval.run.succeeded or realtime.call.incoming.
 */
readonly class OpenAiWebhookReceived
{
    /**
     * @param string $type The event type, e.g. 'batch.completed'
     * @param array<string, mixed> $data The event's data object (typically holds the resource id)
     * @param array<string, mixed> $payload The full webhook payload
     * @param string|null $eventId The event id (evt_...), when provided
     */
    public function __construct(
        public string $type,
        public array $data,
        public array $payload,
        public ?string $eventId = null,
    ) {
    }
}
