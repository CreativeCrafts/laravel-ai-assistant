<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\CertificatesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/certificates', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/certificates')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/certificates/{certificateId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('certificateId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/certificates/certificateId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/certificates/{certificateId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('certificateId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/certificates/certificateId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/certificates', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/certificates?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/certificates/{certificateId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('certificateId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/certificates/certificateId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends activate as POST /v1/organization/certificates/activate', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->activate(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/certificates/activate')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends deactivate as POST /v1/organization/certificates/deactivate', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->deactivate(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/certificates/deactivate')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends listForProject as GET /v1/organization/projects/{projectId}/certificates', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->listForProject('projectId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/certificates?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends activateForProject as POST /v1/organization/projects/{projectId}/certificates/activate', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->activateForProject('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/certificates/activate')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends deactivateForProject as POST /v1/organization/projects/{projectId}/certificates/deactivate', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CertificatesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->deactivateForProject('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/certificates/deactivate')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
