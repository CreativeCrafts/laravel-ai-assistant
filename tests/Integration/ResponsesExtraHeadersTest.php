<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesInputItemsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Psr7\Response;

it('sends extra headers with createResponse', function () {
    $http = RecordingHttpClient::json(['id' => 'resp_1']);
    $repository = new ResponsesHttpRepository($http->transport());

    expect($repository->createResponse(['model' => 'gpt-5', 'input' => 'hi'], ['OpenAI-Beta' => 'responses_multi_agent=v1']))
        ->toBe(['id' => 'resp_1']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('responses_multi_agent=v1')
        ->and($request->getHeaderLine('Idempotency-Key'))->not->toBe('')
        ->and($http->lastJson())->toBe(['model' => 'gpt-5', 'input' => 'hi']);
});

it('sends extra headers with streamResponse', function () {
    $http = RecordingHttpClient::sse('{"type":"response.completed"}');
    $repository = new ResponsesHttpRepository($http->transport());

    iterator_to_array($repository->streamResponse(['model' => 'gpt-5', 'input' => 'hi'], ['OpenAI-Beta' => 'responses_multi_agent=v1']));

    $request = $http->lastRequest();
    expect($request->getHeaderLine('OpenAI-Beta'))->toBe('responses_multi_agent=v1')
        ->and($request->getHeaderLine('Accept'))->toBe('text/event-stream')
        ->and($http->lastJson())->toBe(['model' => 'gpt-5', 'input' => 'hi', 'stream' => true]);
});

it('sends no OpenAI-Beta header by default', function () {
    $http = RecordingHttpClient::json(['id' => 'resp_1']);
    (new ResponsesHttpRepository($http->transport()))->createResponse(['model' => 'gpt-5', 'input' => 'hi']);

    expect($http->lastRequest()->hasHeader('OpenAI-Beta'))->toBeFalse();
});

it('sends extra headers with the other Responses requests', function (string $repository, string $method, array $arguments, string $verb, string $uri) {
    $http = RecordingHttpClient::json(['id' => 'resp_1']);

    (new $repository($http->transport()))->{$method}(...[...$arguments, ['OpenAI-Beta' => 'responses_multi_agent=v1']]);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe($verb)
        ->and($http->lastUri())->toBe($uri)
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('responses_multi_agent=v1');
})->with([
    'retrieve' => [ResponsesHttpRepository::class, 'getResponse', ['resp_1', []], 'GET', 'https://api.openai.com/v1/responses/resp_1'],
    'cancel' => [ResponsesHttpRepository::class, 'cancelResponse', ['resp_1'], 'POST', 'https://api.openai.com/v1/responses/resp_1/cancel'],
    'delete' => [ResponsesHttpRepository::class, 'deleteResponse', ['resp_1'], 'DELETE', 'https://api.openai.com/v1/responses/resp_1'],
    'compact' => [ResponsesHttpRepository::class, 'compactResponse', [['model' => 'gpt-5', 'input' => 'hi']], 'POST', 'https://api.openai.com/v1/responses/compact'],
    'input tokens' => [ResponsesHttpRepository::class, 'countInputTokens', [['model' => 'gpt-5', 'input' => 'hi']], 'POST', 'https://api.openai.com/v1/responses/input_tokens'],
    'input items' => [ResponsesInputItemsHttpRepository::class, 'list', ['resp_1', []], 'GET', 'https://api.openai.com/v1/responses/resp_1/input_items'],
]);

it('sends extra headers when resuming a stream', function () {
    $http = RecordingHttpClient::sse('{"type":"response.completed"}');

    iterator_to_array((new ResponsesHttpRepository($http->transport()))->resumeStream('resp_1', [], ['OpenAI-Beta' => 'responses_multi_agent=v1']), false);

    $request = $http->lastRequest();
    expect($request->getHeaderLine('OpenAI-Beta'))->toBe('responses_multi_agent=v1')
        ->and($request->getHeaderLine('Accept'))->toBe('text/event-stream')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses/resp_1?stream=true');
});

it('sends withHeaders() with every stored-response request of Ai::responses()', function () {
    $json = fn (string $body) => new Response(200, ['Content-Type' => 'application/json'], $body);
    $http = new RecordingHttpClient(
        $json('{"id":"resp_1","status":"completed"}'),
        $json('{"object":"list","data":[]}'),
        $json('{"object":"response.input_tokens","input_tokens":42}'),
        $json('{"object":"response.compaction"}'),
        $json('{"id":"resp_1","status":"cancelled"}'),
        $json('{"id":"resp_1","deleted":true}'),
        new Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"type\":\"response.completed\"}\n\n"),
    );
    app()->instance(OpenAITransport::class, $http->transport());

    $responses = Ai::responses()->withHeaders(['OpenAI-Beta' => 'responses_multi_agent=v1']);
    $responses->retrieve('resp_1');
    $responses->listInputItems('resp_1');
    $responses->countInputTokens(['model' => 'gpt-5', 'input' => 'Hi']);
    $responses->compact(['model' => 'gpt-5', 'input' => []]);
    $responses->cancel('resp_1');
    $responses->delete('resp_1');
    iterator_to_array($responses->resume('resp_1'), false);

    expect(array_map(fn (array $entry) => $entry['request']->getMethod() . ' ' . $entry['request']->getUri()->getPath(), $http->history))
        ->toBe([
            'GET /v1/responses/resp_1',
            'GET /v1/responses/resp_1/input_items',
            'POST /v1/responses/input_tokens',
            'POST /v1/responses/compact',
            'POST /v1/responses/resp_1/cancel',
            'DELETE /v1/responses/resp_1',
            'GET /v1/responses/resp_1',
        ])
        ->and(array_map(fn (array $entry) => $entry['request']->getHeaderLine('OpenAI-Beta'), $http->history))
        ->toBe(array_fill(0, 7, 'responses_multi_agent=v1'));
});

it('sends the multi-agent beta header and body field with Ai::responses()->send()', function () {
    $http = RecordingHttpClient::json([
        'id' => 'resp_1',
        'object' => 'response',
        'status' => 'completed',
        'output' => [['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'Done']]]],
    ]);
    app()->instance(OpenAITransport::class, $http->transport());

    Ai::responses()
        ->inConversation('conv_1')
        ->withHeaders(['OpenAI-Beta' => 'responses_multi_agent=v1'])
        ->withOptions(['multi_agent' => ['enabled' => true, 'max_concurrent_subagents' => 2]])
        ->input()->message('Compare three suppliers')
        ->send();

    expect($http->history)->toHaveCount(1)
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/responses')
        ->and($http->lastRequest()->getHeaderLine('OpenAI-Beta'))->toBe('responses_multi_agent=v1')
        ->and($http->lastJson()['multi_agent'])->toBe(['enabled' => true, 'max_concurrent_subagents' => 2])
        ->and($http->lastJson()['conversation'])->toBe('conv_1');
});

describe('ResponsesBuilder::withHeaders', function () {
    beforeEach(function () {
        config()->set('ai-assistant.api_key', 'test_key_123');
        $this->responses = new FakeResponsesRepository();
        app()->instance(ResponsesRepositoryContract::class, $this->responses);
        app()->instance(ConversationsRepositoryContract::class, new FakeConversationsRepository());
    });

    it('passes headers through send()', function () {
        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withHeaders(['OpenAI-Beta' => 'responses_multi_agent=v1'])
            ->withHeaders(['X-Trace' => 'abc'])
            ->inputItems()->appendUserText('Hi');

        $builder->send();

        expect($this->responses->lastHeaders)->toBe(['OpenAI-Beta' => 'responses_multi_agent=v1', 'X-Trace' => 'abc']);
    });

    it('replaces a header set earlier under any letter case', function () {
        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withHeaders(['OpenAI-Beta' => 'first', 'X-Trace' => 'abc'])
            ->withHeaders(['openai-beta' => 'second'])
            ->inputItems()->appendUserText('Hi');

        $builder->send();

        expect($this->responses->lastHeaders)->toBe(['X-Trace' => 'abc', 'openai-beta' => 'second']);
    });

    it('passes headers through the unified input send()', function () {
        (new ResponsesBuilder(app(AssistantService::class)))
            ->inConversation('conv_1')
            ->withHeaders(['OpenAI-Beta' => 'responses_multi_agent=v1'])
            ->input()->message('Hi')
            ->send();

        expect($this->responses->lastHeaders)->toBe(['OpenAI-Beta' => 'responses_multi_agent=v1']);
    });

    it('keeps the headers on the request that continues a turn after tool calls', function () {
        $this->responses->pushResponse(ResponsesFactory::withToolCalls('conv_1', [
            ['id' => 'call_1', 'name' => 'unregistered_tool', 'arguments' => ['x' => 1]],
        ]));
        $this->responses->pushResponse(ResponsesFactory::afterToolResultsFinal('conv_1', 'done'));

        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withHeaders(['OpenAI-Beta' => 'responses_multi_agent=v1'])
            ->inputItems()->appendUserText('Hi');

        $builder->send();

        expect($this->responses->createdHeaders)->toBe([
            ['OpenAI-Beta' => 'responses_multi_agent=v1'],
            ['OpenAI-Beta' => 'responses_multi_agent=v1'],
        ]);
    });

    it('passes headers through stream()', function () {
        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withHeaders(['OpenAI-Beta' => 'responses_multi_agent=v1'])
            ->inputItems()->appendUserText('Hi');

        iterator_to_array($builder->stream());

        expect($this->responses->lastHeaders)->toBe(['OpenAI-Beta' => 'responses_multi_agent=v1']);
    });
});
