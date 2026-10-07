<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ContentProvenanceChecksHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

beforeEach(function () {
    $this->fixture = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('sends create as POST /v1/content_provenance_checks', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContentProvenanceChecksHttpRepository($http->transport());

    expect($repository->create(['file' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/content_provenance_checks')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="file"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});
