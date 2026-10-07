<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ModelsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\RealtimeHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\UploadsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

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

/**
 * Responses for an upload of three parts: create, three parts, complete.
 */
function threePartUploadClient(): RecordingHttpClient
{
    return new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"upload_1","status":"pending"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"part_1"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"part_2"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"part_3"}'),
        new Response(200, ['Content-Type' => 'application/json'], '{"id":"upload_1","status":"completed"}'),
    );
}

it('sends each upload part from its own byte range of the file', function () {
    $path = (string)tempnam(sys_get_temp_dir(), 'upload_');
    file_put_contents($path, 'abcdefghij');
    $http = threePartUploadClient();

    (new UploadsHttpRepository($http->transport()))->uploadFile($path, 'batch', 'text/plain', 4);
    $parts = array_map(fn (array $entry): string => (string)$entry['request']->getBody(), array_slice($http->history, 1, 3));
    unlink($path);

    expect($parts[0])->toContain("\r\n\r\nabcd\r\n")
        ->and($parts[1])->toContain("\r\n\r\nefgh\r\n")
        ->and($parts[2])->toContain("\r\n\r\nij\r\n");
});

it('streams upload parts instead of buffering them in memory', function () {
    $partSize = 8 * 1024 * 1024;
    $path = (string)tempnam(sys_get_temp_dir(), 'upload_');
    $handle = fopen($path, 'wb');
    ftruncate($handle, 3 * $partSize);
    fclose($handle);

    // Answers any number of parts and drains each request body in small reads, like curl does
    $sent = 0;
    $handler = HandlerStack::create(function (RequestInterface $request) use (&$sent): PromiseInterface {
        $body = $request->getBody();
        while (!$body->eof()) {
            $sent += strlen($body->read(65536));
        }
        $path = $request->getUri()->getPath();

        return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], match (true) {
            str_ends_with($path, '/parts') => '{"id":"part_1"}',
            str_ends_with($path, '/complete') => '{"id":"upload_1","status":"completed"}',
            default => '{"id":"upload_1","status":"pending"}',
        }));
    });
    $transport = new GuzzleOpenAITransport(new GuzzleClient(['handler' => $handler, 'base_uri' => 'https://api.openai.com']));

    memory_reset_peak_usage();
    $before = memory_get_usage();
    $upload = (new UploadsHttpRepository($transport))->uploadFile($path, 'batch', 'application/octet-stream', $partSize);
    $growth = memory_get_peak_usage() - $before;
    unlink($path);

    expect($upload['status'])->toBe('completed')
        ->and($sent)->toBeGreaterThan(3 * $partSize)
        ->and($growth)->toBeLessThan(intdiv($partSize, 2));
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
