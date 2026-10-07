<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\CompletionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/completions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new CompletionsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/completions')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends stream as POST /v1/completions', function () {
    $http = RecordingHttpClient::sse('{"type":"probe.event"}');
    $repository = new CompletionsHttpRepository($http->transport());

    expect(iterator_to_array($repository->stream(['probe' => 'value']), false))->toBe([['type' => 'probe.event']]);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/completions')
        ->and($http->lastJson())->toBe(['probe' => 'value', 'stream' => true])
        ->and($request->getHeaderLine('Accept'))->toBe('text/event-stream');
});
