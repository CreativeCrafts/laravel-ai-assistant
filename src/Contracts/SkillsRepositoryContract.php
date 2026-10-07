<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Skills API: reusable bundles of instructions and files for the shell tool.
 * Resolve through Ai::skills().
 */
interface SkillsRepositoryContract
{
    /**
     * Create a new skill.
     *
     * POST /v1/skills
     *
     * @param array<string, mixed> $payload Multipart fields; files accepts a path, SplFileInfo, stream or explicit part
     */
    public function create(array $payload): array;

    /**
     * Get a skill by its ID.
     *
     * GET /v1/skills/{skillId}
     */
    public function retrieve(string $skillId): array;

    /**
     * Update the default version pointer for a skill.
     *
     * POST /v1/skills/{skillId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $skillId, array $payload): array;

    /**
     * List all skills for the current project.
     *
     * GET /v1/skills
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Delete a skill by its ID.
     *
     * DELETE /v1/skills/{skillId}
     */
    public function delete(string $skillId): array;

    /**
     * Download a skill zip bundle by its ID.
     *
     * GET /v1/skills/{skillId}/content
     *
     * @return array{content: string, content_type: string}
     */
    public function content(string $skillId): array;

    /**
     * Create a new immutable skill version.
     *
     * POST /v1/skills/{skillId}/versions
     *
     * @param array<string, mixed> $payload Multipart fields; files accepts a path, SplFileInfo, stream or explicit part
     */
    public function createVersion(string $skillId, array $payload): array;

    /**
     * Get a specific skill version.
     *
     * GET /v1/skills/{skillId}/versions/{version}
     */
    public function retrieveVersion(string $skillId, string $version): array;

    /**
     * List skill versions for a skill.
     *
     * GET /v1/skills/{skillId}/versions
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listVersions(string $skillId, array $params = []): array;

    /**
     * Delete a skill version.
     *
     * DELETE /v1/skills/{skillId}/versions/{version}
     */
    public function deleteVersion(string $skillId, string $version): array;

    /**
     * Download a skill version zip bundle.
     *
     * GET /v1/skills/{skillId}/versions/{version}/content
     *
     * @return array{content: string, content_type: string}
     */
    public function versionContent(string $skillId, string $version): array;
}
