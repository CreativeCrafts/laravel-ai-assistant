<?php

declare(strict_types=1);

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
