<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

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

    it('passes headers through the unified input send()', function () {
        (new ResponsesBuilder(app(AssistantService::class)))
            ->inConversation('conv_1')
            ->withHeaders(['OpenAI-Beta' => 'responses_multi_agent=v1'])
            ->input()->message('Hi')
            ->send();

        expect($this->responses->lastHeaders)->toBe(['OpenAI-Beta' => 'responses_multi_agent=v1']);
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
