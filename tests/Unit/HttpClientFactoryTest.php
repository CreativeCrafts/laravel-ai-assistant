<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\HttpClientFactory;

it('creates a client with base config and headers', function () {
    $factory = new HttpClientFactory(
        baseUri: 'https://example.test',
        defaultHeaders: ['Authorization' => 'Bearer test', 'Accept' => 'application/json'],
        timeout: 5.0,
        connectTimeout: 2.0
    );

    $client = $factory->make();

    $baseUri = $client->getConfig('base_uri');
    $baseUri = is_object($baseUri) ? (string)$baseUri : (string)$baseUri;

    expect($baseUri)->toBe('https://example.test')
        ->and($client->getConfig('headers')['Authorization'])->toBe('Bearer test')
        ->and($client->getConfig('timeout'))->toBe(5.0)
        ->and($client->getConfig('connect_timeout'))->toBe(2.0);
});

it('allows overriding headers and timeout per client', function () {
    $factory = new HttpClientFactory(
        baseUri: 'https://example.test',
        defaultHeaders: ['Authorization' => 'Bearer test', 'Accept' => 'application/json'],
        timeout: 5.0,
        connectTimeout: 2.0
    );

    $client = $factory->make(['Authorization' => 'Bearer override', 'X-Test' => '1'], 9.0);

    expect($client->getConfig('headers')['Authorization'])->toBe('Bearer override')
        ->and($client->getConfig('headers')['X-Test'])->toBe('1')
        ->and($client->getConfig('timeout'))->toBe(9.0);
});
