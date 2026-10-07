<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ContentProvenanceChecksRepositoryContract;

/**
 * @internal Use Ai::contentProvenanceChecks() instead.
 */
final readonly class ContentProvenanceChecksHttpRepository extends AbstractHttpRepository implements ContentProvenanceChecksRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postForm('content_provenance_checks', $payload, ['file']);
    }
}
