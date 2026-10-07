<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ModelsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\RealtimeHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\UploadsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use GuzzleHttp\Psr7\Response;

it('creates WebRTC realtime calls and returns the SDP answer with the call id', function () {
    $http = new RecordingHttpClient(new Response(201, [
        'Content-Type' => 'application/sdp',
        'Location' => '/v1/realtime/calls/rtc_123',
    ], "v=0\r\no=- answer"));

    $out = (new RealtimeHttpRepository($http->transport()))->createCall("v=0\r\no=- offer", ['type' => 'realtime', 'model' => 'gpt-realtime']);

    $body = $http->lastBody();
    expect($out)->toBe(['sdp' => "v=0\r\no=- answer", 'call_id' => 'rtc_123'])
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/calls')
        ->and($http->lastRequest()->getHeaderLine('Accept'))->toBe('application/sdp')
        ->and($body)->toContain("Content-Type: application/sdp\r\nContent-Disposition: form-data; name=\"sdp\"")
        ->and($body)->toContain("v=0\r\no=- offer")
        ->and($body)->toContain("Content-Type: application/json\r\nContent-Disposition: form-data; name=\"session\"")
        ->and($body)->toContain('{"type":"realtime","model":"gpt-realtime"}');
});

it('uploads large files in parts and completes the upload', function () {
    $path = tempnam(sys_get_temp_dir(), 'upload_') . '.jsonl';
    file_put_contents($path, str_repeat('a', 10));

    $http = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"upload_1","status":"pending"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"part_1"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"part_2"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"part_3"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"upload_1","status":"completed","file":{"id":"file_1"}}'),
    );

    $out = (new UploadsHttpRepository($http->transport()))->uploadFile($path, 'batch', partSize: 4);
    unlink($path);

    $requests = array_map(fn (array $entry) => $entry['request'], $http->history);
    $create = json_decode((string)$requests[0]->getBody(), true);
    $complete = json_decode((string)$requests[4]->getBody(), true);

    expect($out['file']['id'])->toBe('file_1')
        ->and($requests)->toHaveCount(5)
        ->and((string)$requests[0]->getUri())->toBe('https://api.openai.com/v1/uploads')
        ->and($create)->toMatchArray(['bytes' => 10, 'purpose' => 'batch'])
        ->and((string)$requests[1]->getUri())->toBe('https://api.openai.com/v1/uploads/upload_1/parts')
        ->and((string)$requests[1]->getBody())->toContain("aaaa\r\n")
        ->and((string)$requests[3]->getBody())->toContain("aa\r\n")
        ->and((string)$requests[4]->getUri())->toBe('https://api.openai.com/v1/uploads/upload_1/complete')
        ->and($complete['part_ids'])->toBe(['part_1', 'part_2', 'part_3'])
        ->and($complete['md5'])->toBe(md5(str_repeat('a', 10)));
});

it('rejects unreadable upload sources', function () {
    (new UploadsHttpRepository((new RecordingHttpClient())->transport()))->uploadFile('/missing/file.jsonl', 'batch');
})->throws(FileValidationException::class);

it('encodes path parameters like the official SDKs', function () {
    $http = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"ok"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"ok"}'),
    );
    $repository = new ModelsHttpRepository($http->transport());

    $repository->retrieve('ft:gpt-4o-mini-2024-07-18:acme::abc123');
    expect($http->lastUri())->toBe('https://api.openai.com/v1/models/ft:gpt-4o-mini-2024-07-18:acme::abc123');

    $repository->retrieve('a/b?c#d');
    expect($http->lastUri())->toBe('https://api.openai.com/v1/models/a%2Fb%3Fc%23d');
});

it('rejects empty and dot path parameters', function (string $id) {
    (new ModelsHttpRepository((new RecordingHttpClient())->transport()))->retrieve($id);
})->with(['', '.', '..'])->throws(InvalidArgumentException::class, 'Invalid path parameter');
