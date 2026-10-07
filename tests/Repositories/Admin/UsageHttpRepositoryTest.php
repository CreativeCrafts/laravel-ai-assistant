<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\UsageHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends completions as GET /v1/organization/usage/completions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->completions(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/completions?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends embeddings as GET /v1/organization/usage/embeddings', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->embeddings(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/embeddings?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends moderations as GET /v1/organization/usage/moderations', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->moderations(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/moderations?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends images as GET /v1/organization/usage/images', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->images(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/images?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends audioSpeeches as GET /v1/organization/usage/audio_speeches', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->audioSpeeches(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/audio_speeches?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends audioTranscriptions as GET /v1/organization/usage/audio_transcriptions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->audioTranscriptions(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/audio_transcriptions?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends vectorStores as GET /v1/organization/usage/vector_stores', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->vectorStores(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/vector_stores?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends codeInterpreterSessions as GET /v1/organization/usage/code_interpreter_sessions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->codeInterpreterSessions(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/code_interpreter_sessions?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends fileSearchCalls as GET /v1/organization/usage/file_search_calls', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->fileSearchCalls(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/file_search_calls?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends webSearchCalls as GET /v1/organization/usage/web_search_calls', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->webSearchCalls(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/usage/web_search_calls?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends costs as GET /v1/organization/costs', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->costs(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/costs?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
