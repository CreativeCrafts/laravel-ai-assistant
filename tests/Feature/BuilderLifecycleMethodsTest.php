<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Exceptions\MissingRequiredParameterException;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Psr7\Response;

function bindRecordingTransport(RecordingHttpClient $http): RecordingHttpClient
{
    app()->instance(OpenAITransport::class, $http->transport());

    return $http;
}

it('manages stored responses through Ai::responses()', function () {
    $json = fn (string $body) => new Response(200, ['Content-Type' => 'application/json'], $body);
    $http = bindRecordingTransport(new RecordingHttpClient(
        $json('{"id":"resp_1","status":"completed"}'),
        $json('{"object":"list","data":[]}'),
        $json('{"object":"response.input_tokens","input_tokens":42}'),
        $json('{"object":"response.compaction"}'),
        $json('{"id":"resp_1","status":"cancelled"}'),
        $json('{"id":"resp_1","deleted":true}'),
    ));
    $responses = Ai::responses();

    expect($responses->retrieve('resp_1')['status'])->toBe('completed')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1');

    $responses->listInputItems('resp_1', ['limit' => 1]);
    expect($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1/input_items?limit=1');

    expect($responses->countInputTokens(['model' => 'gpt-5', 'input' => 'Hi'])['input_tokens'])->toBe(42)
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses/input_tokens');

    $responses->compact(['model' => 'gpt-5', 'input' => []]);
    expect($http->lastUri())->toBe('https://api.openai.com/v1/responses/compact');

    expect($responses->cancel('resp_1'))->toBeTrue()
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1/cancel')
        ->and($responses->delete('resp_1'))->toBeTrue()
        ->and($http->lastRequest()->getMethod())->toBe('DELETE');
});

it('resumes a background response stream', function () {
    $http = bindRecordingTransport(RecordingHttpClient::sse('{"type":"response.completed","sequence_number":9}'));

    $events = iterator_to_array(Ai::responses()->resume('resp_1', startingAfter: 8), false);

    expect($events)->toBe([['type' => 'response.completed', 'sequence_number' => 9]])
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1?starting_after=8&stream=true');
});

it('manages conversation items through Ai::conversations()', function () {
    $json = fn (string $body) => new Response(200, ['Content-Type' => 'application/json'], $body);
    $http = bindRecordingTransport(new RecordingHttpClient(
        $json('{"id":"conv_1","metadata":{"topic":"billing"}}'),
        $json('{"object":"list","data":[{"id":"msg_9"}]}'),
        $json('{"id":"msg_9","type":"message"}'),
        $json('{"id":"conv_1","object":"conversation"}'),
        $json('{"id":"conv_1","deleted":true}'),
    ));
    $conversation = Ai::conversations()->use('conv_1');

    expect($conversation->update(['topic' => 'billing'])['metadata'])->toBe(['topic' => 'billing'])
        ->and($http->lastJson())->toBe(['metadata' => ['topic' => 'billing']]);

    $conversation->addItems([['type' => 'message', 'role' => 'user', 'content' => 'Hello']]);
    expect($http->lastUri())->toBe('https://api.openai.com/v1/conversations/conv_1/items');

    expect($conversation->item('msg_9')['id'])->toBe('msg_9')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/conversations/conv_1/items/msg_9');

    expect($conversation->deleteItem('msg_9'))->toBeTrue()
        ->and($http->lastRequest()->getMethod())->toBe('DELETE')
        ->and($conversation->delete())->toBeTrue()
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/conversations/conv_1');
});

it('requires an active conversation instead of creating one', function (string $method, array $arguments) {
    $http = bindRecordingTransport(new RecordingHttpClient());

    expect(fn () => Ai::conversations()->{$method}(...$arguments))
        ->toThrow(MissingRequiredParameterException::class, 'No active conversation')
        ->and($http->history)->toBe([]);
})->with([
    'retrieve' => ['retrieve', []],
    'update' => ['update', [['topic' => 'billing']]],
    'delete' => ['delete', []],
    'item' => ['item', ['msg_1']],
    'addItems' => ['addItems', [[['type' => 'message', 'role' => 'user', 'content' => 'Hi']]]],
    'deleteItem' => ['deleteItem', ['msg_1']],
]);

it('percent-encodes ids so they cannot reach other endpoints', function () {
    $json = fn (string $body) => new Response(200, ['Content-Type' => 'application/json'], $body);
    $http = bindRecordingTransport(new RecordingHttpClient(
        $json('{"deleted":true}'),
        $json('{"id":"x"}'),
        $json('{"object":"list","data":[]}'),
        $json('{"object":"list","data":[]}'),
    ));

    Ai::conversations()->use('conv_1')->deleteItem('../../../files/file-1');
    expect($http->lastRequest()->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/conversations/conv_1/items/..%2F..%2F..%2Ffiles%2Ffile-1');

    Ai::responses()->retrieve('resp/1?x#y');
    expect($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp%2F1%3Fx%23y');

    Ai::responses()->listInputItems('../files');
    expect($http->lastUri())->toBe('https://api.openai.com/v1/responses/..%2Ffiles/input_items');

    Ai::vectorStores()->search('vs/../../files', ['query' => 'refunds']);
    expect($http->lastUri())->toBe('https://api.openai.com/v1/vector_stores/vs%2F..%2F..%2Ffiles/search');
});
