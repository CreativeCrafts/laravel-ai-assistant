<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ConversationsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesInputItemsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoresHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('retrieves responses with include parameters', function () {
    $http = RecordingHttpClient::json(['id' => 'resp_1']);

    (new ResponsesHttpRepository($http->transport()))->getResponse('resp_1', ['include' => ['message.output_text.logprobs']]);

    expect($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1?include%5B%5D=message.output_text.logprobs');
});

it('resumes streaming a background response', function () {
    $http = RecordingHttpClient::sse('{"type":"response.output_text.delta","delta":"Hi","sequence_number":5}', '{"type":"response.completed"}');

    $events = iterator_to_array((new ResponsesHttpRepository($http->transport()))->resumeStream('resp_1', ['starting_after' => 4]), false);

    expect(array_column($events, 'type'))->toBe(['response.output_text.delta', 'response.completed'])
        ->and($http->lastRequest()->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1?starting_after=4&stream=true');
});

it('compacts conversations and counts input tokens', function (string $method, string $path) {
    $http = RecordingHttpClient::json(['object' => 'ok']);
    $repository = new ResponsesHttpRepository($http->transport());

    expect($repository->{$method}(['model' => 'gpt-5', 'input' => 'Hello']))->toBe(['object' => 'ok'])
        ->and($http->lastRequest()->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe("https://api.openai.com/v1/{$path}")
        ->and($http->lastJson())->toBe(['model' => 'gpt-5', 'input' => 'Hello']);
})->with([
    'compact' => ['compactResponse', 'responses/compact'],
    'input tokens' => ['countInputTokens', 'responses/input_tokens'],
]);

it('lists response input items from the input_items endpoint', function () {
    $http = RecordingHttpClient::json(['object' => 'list', 'data' => []]);

    (new ResponsesInputItemsHttpRepository($http->transport()))->list('resp_1', ['limit' => 5, 'order' => 'asc']);

    expect($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1/input_items?limit=5&order=asc');
});

it('retrieves a conversation item and creates items with include parameters', function () {
    $http = new RecordingHttpClient(
        new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"id":"msg_1"}'),
        new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"object":"list"}'),
    );
    $repository = new ConversationsHttpRepository($http->transport());

    expect($repository->getItem('conv_1', 'msg_1', ['include' => ['message.input_image.image_url']]))->toBe(['id' => 'msg_1'])
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/conversations/conv_1/items/msg_1?include%5B%5D=message.input_image.image_url');

    $repository->createItems('conv_1', [['type' => 'message', 'role' => 'user', 'content' => 'Hi']], ['include' => ['message.output_text.logprobs']]);

    expect($http->lastUri())->toBe('https://api.openai.com/v1/conversations/conv_1/items?include%5B%5D=message.output_text.logprobs')
        ->and($http->lastJson()['items'][0]['content'])->toBe('Hi');
});

it('lists files and uploads with an expiration policy', function () {
    $http = new RecordingHttpClient(
        new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"object":"list","data":[]}'),
        new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"id":"file_1"}'),
    );
    $repository = new FilesHttpRepository($http->transport());

    $repository->list(['purpose' => 'batch', 'limit' => 10]);
    expect($http->lastUri())->toBe('https://api.openai.com/v1/files?purpose=batch&limit=10');

    $out = $repository->upload(__DIR__ . '/../fixtures/test-audio.mp3', 'user_data', [
        'expires_after' => ['anchor' => 'created_at', 'seconds' => 3600],
    ]);

    expect($out)->toBe(['id' => 'file_1'])
        ->and($http->lastBody())->toContain('name="file"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain("name=\"purpose\"\r\nContent-Length: 9\r\n\r\nuser_data")
        ->and($http->lastBody())->toContain("name=\"expires_after[anchor]\"\r\nContent-Length: 10\r\n\r\ncreated_at")
        ->and($http->lastBody())->toContain("name=\"expires_after[seconds]\"\r\nContent-Length: 4\r\n\r\n3600");
});

it('searches vector stores', function () {
    $http = RecordingHttpClient::json(['object' => 'vector_store.search_results.page', 'data' => []]);

    (new VectorStoresHttpRepository($http->transport()))->search('vs_1', ['query' => 'refund policy', 'max_num_results' => 3]);

    expect($http->lastRequest()->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/vector_stores/vs_1/search')
        ->and($http->lastJson())->toBe(['query' => 'refund policy', 'max_num_results' => 3])
        ->and($http->lastRequest()->getHeaderLine('OpenAI-Beta'))->toBe('assistants=v2');
});
