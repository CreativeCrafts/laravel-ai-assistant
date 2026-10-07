<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\VaultsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/vaults', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieve as GET /v1/vaults/{vaultId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->retrieve('vaultId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends update as POST /v1/vaults/{vaultId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->update('vaultId_1', ['name' => 'Production']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1')
        ->and($http->lastJson())->toBe(['name' => 'Production'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends list as GET /v1/vaults', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends delete as DELETE /v1/vaults/{vaultId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->delete('vaultId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends createCredential as POST /v1/vaults/{vaultId}/credentials', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->createCredential('vaultId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1/credentials')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieveCredential as GET /v1/vaults/{vaultId}/credentials/{credentialId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->retrieveCredential('vaultId_1', 'credentialId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1/credentials/credentialId_2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends updateCredential as POST /v1/vaults/{vaultId}/credentials/{credentialId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->updateCredential('vaultId_1', 'credentialId_2', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1/credentials/credentialId_2')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listCredentials as GET /v1/vaults/{vaultId}/credentials', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->listCredentials('vaultId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1/credentials?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends deleteCredential as DELETE /v1/vaults/{vaultId}/credentials/{credentialId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VaultsHttpRepository($http->transport());

    expect($repository->deleteCredential('vaultId_1', 'credentialId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vaults/vaultId_1/credentials/credentialId_2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});
