<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\InvitesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/invites', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new InvitesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/invites')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/invites/{inviteId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new InvitesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('inviteId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/invites/inviteId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/invites', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new InvitesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/invites?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/invites/{inviteId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new InvitesHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('inviteId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/invites/inviteId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
