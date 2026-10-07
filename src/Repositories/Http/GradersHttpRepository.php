<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\GradersRepositoryContract;

/**
 * @internal Use Ai::graders() instead.
 */
final readonly class GradersHttpRepository extends AbstractHttpRepository implements GradersRepositoryContract
{
    public function run(array $payload): array
    {
        return $this->postJson('fine_tuning/alpha/graders/run', $payload);
    }

    public function validate(array $payload): array
    {
        return $this->postJson('fine_tuning/alpha/graders/validate', $payload);
    }
}
