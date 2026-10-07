<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Usage and costs reporting.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->usage().
 */
interface UsageRepositoryContract
{
    /**
     * Get completions usage details for the organization.
     *
     * GET /v1/organization/usage/completions
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function completions(array $params = []): array;

    /**
     * Get embeddings usage details for the organization.
     *
     * GET /v1/organization/usage/embeddings
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function embeddings(array $params = []): array;

    /**
     * Get moderations usage details for the organization.
     *
     * GET /v1/organization/usage/moderations
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function moderations(array $params = []): array;

    /**
     * Get images usage details for the organization.
     *
     * GET /v1/organization/usage/images
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function images(array $params = []): array;

    /**
     * Get audio speeches usage details for the organization.
     *
     * GET /v1/organization/usage/audio_speeches
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function audioSpeeches(array $params = []): array;

    /**
     * Get audio transcriptions usage details for the organization.
     *
     * GET /v1/organization/usage/audio_transcriptions
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function audioTranscriptions(array $params = []): array;

    /**
     * Get vector stores usage details for the organization.
     *
     * GET /v1/organization/usage/vector_stores
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function vectorStores(array $params = []): array;

    /**
     * Get code interpreter sessions usage details for the organization.
     *
     * GET /v1/organization/usage/code_interpreter_sessions
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function codeInterpreterSessions(array $params = []): array;

    /**
     * Get file search calls usage details for the organization.
     *
     * GET /v1/organization/usage/file_search_calls
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function fileSearchCalls(array $params = []): array;

    /**
     * Get web search calls usage details for the organization.
     *
     * GET /v1/organization/usage/web_search_calls
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function webSearchCalls(array $params = []): array;

    /**
     * Get costs details for the organization.
     *
     * GET /v1/organization/costs
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function costs(array $params = []): array;
}
