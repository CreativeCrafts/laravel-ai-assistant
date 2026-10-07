<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Videos API (Sora): generate, remix, edit and extend videos.
 * Resolve through Ai::videos().
 */
interface VideosRepositoryContract
{
    /**
     * Create a new video generation job from a prompt and optional reference assets.
     *
     * POST /v1/videos
     *
     * @param array<string, mixed> $payload Multipart fields; input_reference accepts a path, SplFileInfo, stream or explicit part
     */
    public function create(array $payload): array;

    /**
     * Fetch the latest metadata for a generated video.
     *
     * GET /v1/videos/{videoId}
     */
    public function retrieve(string $videoId): array;

    /**
     * List recently generated videos for the current project.
     *
     * GET /v1/videos
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Permanently delete a completed or failed video and its stored assets.
     *
     * DELETE /v1/videos/{videoId}
     */
    public function delete(string $videoId): array;

    /**
     * Create a remix of a completed video using a refreshed prompt.
     *
     * POST /v1/videos/{videoId}/remix
     *
     * @param array<string, mixed> $payload
     */
    public function remix(string $videoId, array $payload): array;

    /**
     * Download the generated video bytes or a derived preview asset.
     *
     * GET /v1/videos/{videoId}/content
     *
     * @param array<string, mixed> $params Query parameters
     * @return array{content: string, content_type: string}
     */
    public function downloadContent(string $videoId, array $params = []): array;

    /**
     * Create a new video generation job by editing a source video or existing generated video.
     *
     * POST /v1/videos/edits
     *
     * @param array<string, mixed> $payload Multipart fields; video accepts a path, SplFileInfo, stream or explicit part
     */
    public function edit(array $payload): array;

    /**
     * Create an extension of a completed video.
     *
     * POST /v1/videos/extensions
     *
     * @param array<string, mixed> $payload Multipart fields; video accepts a path, SplFileInfo, stream or explicit part
     */
    public function extend(array $payload): array;

    /**
     * Create a character from an uploaded video.
     *
     * POST /v1/videos/characters
     *
     * @param array<string, mixed> $payload Multipart fields; video accepts a path, SplFileInfo, stream or explicit part
     */
    public function createCharacter(array $payload): array;

    /**
     * Fetch a character.
     *
     * GET /v1/videos/characters/{characterId}
     */
    public function retrieveCharacter(string $characterId): array;
}
