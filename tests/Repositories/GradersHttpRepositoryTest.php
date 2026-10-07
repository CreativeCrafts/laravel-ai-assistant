<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\GradersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends run as POST /v1/fine_tuning/alpha/graders/run', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GradersHttpRepository($http->transport());

    expect($repository->run(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/alpha/graders/run')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends validate as POST /v1/fine_tuning/alpha/graders/validate', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GradersHttpRepository($http->transport());

    expect($repository->validate(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/alpha/graders/validate')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});
