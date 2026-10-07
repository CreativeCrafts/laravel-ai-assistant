<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\AuditLogsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends list as GET /v1/organization/audit_logs', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AuditLogsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/audit_logs?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
