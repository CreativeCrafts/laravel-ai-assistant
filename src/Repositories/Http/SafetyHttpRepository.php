<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\SafetyRepositoryContract;

/**
 * @internal Use Ai::safety() instead.
 */
final readonly class SafetyHttpRepository extends AbstractHttpRepository implements SafetyRepositoryContract
{
    public function retrieveAlert(string $alertId): array
    {
        return $this->getJson($this->path('safety/alerts/%s', $alertId));
    }

    public function retrieveCase(string $caseId): array
    {
        return $this->getJson($this->path('safety/cases/%s', $caseId));
    }
}
