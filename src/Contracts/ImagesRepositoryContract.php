<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Images API: generation, editing and variations (gpt-image and DALL·E models).
 * Resolve through Ai::images().
 */
interface ImagesRepositoryContract
{
    /**
     * Creates an image given a prompt using a GPT Image model. Learn more.
     *
     * POST /v1/images/generations
     *
     * @param array<string, mixed> $payload
     */
    public function generate(array $payload): array;

    /**
     * Stream partial and final images as Server-Sent Events (stream=true, gpt-image models).
     *
     * POST /v1/images/generations
     *
     * @param array<string, mixed> $payload
     * @return iterable<array<string, mixed>> Decoded Server-Sent Events
     */
    public function streamGeneration(array $payload): iterable;

    /**
     * Creates an edited or extended image given one or more source images and a prompt. This endpoint supports GPT Image models.
     *
     * POST /v1/images/edits
     *
     * @param array<string, mixed> $payload Multipart fields; image and mask accept a path, SplFileInfo, stream or explicit part
     */
    public function edit(array $payload): array;

    /**
     * Stream partial and final edited images as Server-Sent Events (stream=true, gpt-image models).
     *
     * POST /v1/images/edits
     *
     * @param array<string, mixed> $payload Multipart fields; image and mask accept a path, SplFileInfo, stream or explicit part
     * @return iterable<array<string, mixed>> Decoded Server-Sent Events
     */
    public function streamEdit(array $payload): iterable;

    /**
     * This endpoint is retired and no longer available. Use the image edits endpoint with a GPT Image model and a prompt to create a variation of an image. The request and response schemas below describe the legacy contract.
     *
     * POST /v1/images/variations
     *
     * @param array<string, mixed> $payload Multipart fields; image accepts a path, SplFileInfo, stream or explicit part
     */
    public function createVariation(array $payload): array;
}
