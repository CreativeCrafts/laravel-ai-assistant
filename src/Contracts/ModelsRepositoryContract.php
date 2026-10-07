<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Models API.
 * Resolve through Ai::models().
 */
interface ModelsRepositoryContract
{
    /**
     * Lists the currently available models, and provides basic information about each one such as the owner and availability.
     *
     * GET /v1/models
     */
    public function list(): array;

    /**
     * Retrieves a model instance, providing basic information about the model such as the owner and permissioning.
     *
     * GET /v1/models/{model}
     */
    public function retrieve(string $model): array;

    /**
     * Delete a fine-tuned model. You must have the Owner role in your organization to delete a model.
     *
     * DELETE /v1/models/{model}
     */
    public function delete(string $model): array;
}
