<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\VideosRepositoryContract;

/**
 * @internal Use Ai::videos() instead.
 */
final readonly class VideosHttpRepository extends AbstractHttpRepository implements VideosRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postForm('videos', $payload, ['input_reference']);
    }

    public function retrieve(string $videoId): array
    {
        return $this->getJson($this->path('videos/%s', $videoId));
    }

    public function list(array $params = []): array
    {
        return $this->getJson('videos', $params);
    }

    public function delete(string $videoId): array
    {
        return $this->deleteJson($this->path('videos/%s', $videoId));
    }

    public function remix(string $videoId, array $payload): array
    {
        return $this->postForm($this->path('videos/%s/remix', $videoId), $payload, []);
    }

    public function downloadContent(string $videoId, array $params = []): array
    {
        return $this->download($this->path('videos/%s/content', $videoId), $params);
    }

    public function edit(array $payload): array
    {
        return $this->postForm('videos/edits', $payload, ['video']);
    }

    public function extend(array $payload): array
    {
        return $this->postForm('videos/extensions', $payload, ['video']);
    }

    public function createCharacter(array $payload): array
    {
        return $this->postForm('videos/characters', $payload, ['video']);
    }

    public function retrieveCharacter(string $characterId): array
    {
        return $this->getJson($this->path('videos/characters/%s', $characterId));
    }
}
