<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Content provenance checks API.
 * Resolve through Ai::contentProvenanceChecks().
 */
interface ContentProvenanceChecksRepositoryContract
{
    /**
     * Check whether an image or audio file contains known OpenAI provenance signals. Learn more about content provenance.
     *
     * POST /v1/content_provenance_checks
     *
     * @param array<string, mixed> $payload Multipart fields; file accepts a path, SplFileInfo, stream or explicit part
     */
    public function create(array $payload): array;
}
