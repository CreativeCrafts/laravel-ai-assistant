<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\SkillsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->fixture = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('sends create as POST /v1/skills', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->create(['files' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="files"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends retrieve as GET /v1/skills/{skillId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->retrieve('skillId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1');
});

it('sends update as POST /v1/skills/{skillId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->update('skillId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends list as GET /v1/skills', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills?limit=2');
});

it('sends delete as DELETE /v1/skills/{skillId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->delete('skillId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1');
});

it('sends content as GET /v1/skills/{skillId}/content', function () {
    $http = new RecordingHttpClient(new Response(200, ['Content-Type' => 'application/octet-stream'], 'BYTES'));
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->content('skillId_1'))->toBe(['content' => 'BYTES', 'content_type' => 'application/octet-stream']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1/content');
});

it('sends createVersion as POST /v1/skills/{skillId}/versions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->createVersion('skillId_1', ['files' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1/versions')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="files"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends retrieveVersion as GET /v1/skills/{skillId}/versions/{version}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->retrieveVersion('skillId_1', 'version_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1/versions/version_2');
});

it('sends listVersions as GET /v1/skills/{skillId}/versions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->listVersions('skillId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1/versions?limit=2');
});

it('sends deleteVersion as DELETE /v1/skills/{skillId}/versions/{version}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->deleteVersion('skillId_1', 'version_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1/versions/version_2');
});

it('sends versionContent as GET /v1/skills/{skillId}/versions/{version}/content', function () {
    $http = new RecordingHttpClient(new Response(200, ['Content-Type' => 'application/octet-stream'], 'BYTES'));
    $repository = new SkillsHttpRepository($http->transport());

    expect($repository->versionContent('skillId_1', 'version_2'))->toBe(['content' => 'BYTES', 'content_type' => 'application/octet-stream']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/skills/skillId_1/versions/version_2/content');
});
