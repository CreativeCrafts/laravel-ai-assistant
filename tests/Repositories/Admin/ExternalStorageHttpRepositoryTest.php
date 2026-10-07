<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ExternalStorageHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/external_storage', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ExternalStorageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/external_storage')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/external_storage/{externalStorageId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ExternalStorageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('externalStorageId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/external_storage/externalStorageId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/external_storage', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ExternalStorageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/external_storage?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/external_storage/{externalStorageId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ExternalStorageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('externalStorageId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/external_storage/externalStorageId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends validate as POST /v1/organization/external_storage/{externalStorageId}/validate', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ExternalStorageHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->validate('externalStorageId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/external_storage/externalStorageId_1/validate')
        ->and($http->lastBody())->toBe('')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
