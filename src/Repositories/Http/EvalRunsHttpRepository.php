<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\EvalRunsRepositoryContract;

/**
 * @internal Use Ai::evalRuns() instead.
 */
final readonly class EvalRunsHttpRepository extends AbstractHttpRepository implements EvalRunsRepositoryContract
{
    public function create(string $evalId, array $payload): array
    {
        return $this->postJson($this->path('evals/%s/runs', $evalId), $payload);
    }

    public function retrieve(string $evalId, string $runId): array
    {
        return $this->getJson($this->path('evals/%s/runs/%s', $evalId, $runId));
    }

    public function list(string $evalId, array $params = []): array
    {
        return $this->getJson($this->path('evals/%s/runs', $evalId), $params);
    }

    public function delete(string $evalId, string $runId): array
    {
        return $this->deleteJson($this->path('evals/%s/runs/%s', $evalId, $runId));
    }

    public function cancel(string $evalId, string $runId): array
    {
        return $this->postJson($this->path('evals/%s/runs/%s/cancel', $evalId, $runId));
    }

    public function listOutputItems(string $evalId, string $runId, array $params = []): array
    {
        return $this->getJson($this->path('evals/%s/runs/%s/output_items', $evalId, $runId), $params);
    }

    public function retrieveOutputItem(string $evalId, string $runId, string $outputItemId): array
    {
        return $this->getJson($this->path('evals/%s/runs/%s/output_items/%s', $evalId, $runId, $outputItemId));
    }
}
