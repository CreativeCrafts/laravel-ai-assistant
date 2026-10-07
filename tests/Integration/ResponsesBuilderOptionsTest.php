<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

it('keeps streaming on when the options try to turn it off', function () {
    $http = RecordingHttpClient::sse('{"type":"response.completed"}');
    app()->instance(OpenAITransport::class, $http->transport());

    $builder = Ai::responses()
        ->inConversation('conv_1')
        ->withOptions(['stream' => false, 'multi_agent' => ['enabled' => true]]);
    $builder->inputItems()->appendUserText('Hi');

    iterator_to_array($builder->stream(), false);

    expect($http->history)->toHaveCount(1)
        ->and($http->lastJson())->toMatchArray([
            'conversation' => 'conv_1',
            'multi_agent' => ['enabled' => true],
            'stream' => true,
        ]);
});

describe('ResponsesBuilder::withOptions', function () {
    beforeEach(function () {
        config()->set('ai-assistant.api_key', 'test_key_123');
        $this->responses = new FakeResponsesRepository();
        app()->instance(ResponsesRepositoryContract::class, $this->responses);
        app()->instance(ConversationsRepositoryContract::class, new FakeConversationsRepository());
    });

    it('sends extra create parameters with send()', function () {
        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withOptions(['multi_agent' => ['enabled' => true], 'store' => true])
            ->withOptions(['store' => false, 'reasoning' => ['effort' => 'low']])
            ->inputItems()->appendUserText('Hi');

        $builder->send();

        expect($this->responses->lastPayload)->toMatchArray([
            'multi_agent' => ['enabled' => true],
            'store' => false,
            'reasoning' => ['effort' => 'low'],
            'conversation' => 'conv_1',
        ])
            ->and($this->responses->lastPayload['input'][0]['content'][0]['text'])->toBe('Hi');
    });

    it('sends extra create parameters with the unified input send()', function () {
        (new ResponsesBuilder(app(AssistantService::class)))
            ->inConversation('conv_1')
            ->withOptions(['multi_agent' => ['enabled' => true]])
            ->input()->message('Hi')
            ->send();

        expect($this->responses->lastPayload['multi_agent'])->toBe(['enabled' => true]);
    });

    it('sends extra create parameters with stream()', function () {
        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withOptions(['multi_agent' => ['enabled' => true], 'stream' => false])
            ->inputItems()->appendUserText('Hi');

        iterator_to_array($builder->stream());

        expect($this->responses->lastPayload['multi_agent'])->toBe(['enabled' => true])
            ->and($this->responses->lastPayload)->not->toHaveKey('stream');
    });

    it('lets builder methods win over options and options win over configured defaults', function () {
        config()->set('ai-assistant.default_model', 'gpt-configured');
        config()->set('ai-assistant.default_instructions', 'Configured instructions');
        config()->set('ai-assistant.responses.max_output_tokens', null);
        config()->set('ai-assistant.max_completion_tokens', 400);

        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')->inputItems()->appendUserText('Hi');
        $builder->send();

        expect($this->responses->lastPayload)->toMatchArray([
            'model' => 'gpt-configured',
            'instructions' => 'Configured instructions',
            'max_output_tokens' => 400,
        ]);

        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withOptions(['model' => 'gpt-5-mini', 'instructions' => 'Option instructions', 'max_output_tokens' => 2000])
            ->inputItems()->appendUserText('Hi');
        $builder->send();

        expect($this->responses->lastPayload)->toMatchArray([
            'model' => 'gpt-5-mini',
            'instructions' => 'Option instructions',
            'max_output_tokens' => 2000,
        ]);

        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->model('gpt-5')
            ->instructions('Builder instructions')
            ->withOptions([
                'model' => 'gpt-5-mini',
                'instructions' => 'Option instructions',
                'conversation' => 'conv_other',
                'input' => 'Other input',
            ])
            ->inputItems()->appendUserText('Hi');
        $builder->send();

        expect($this->responses->lastPayload)->toMatchArray([
            'model' => 'gpt-5',
            'instructions' => 'Builder instructions',
            'conversation' => 'conv_1',
        ])
            ->and($this->responses->lastPayload['input'][0]['content'][0]['text'])->toBe('Hi');

        (new ResponsesBuilder(app(AssistantService::class)))
            ->inConversation('conv_1')
            ->maxCompletionTokens(100)
            ->withOptions(['max_output_tokens' => 2000])
            ->input()->message('Hi')
            ->send();

        expect($this->responses->lastPayload['max_output_tokens'])->toBe(100);
    });

    it('keeps other text options next to the responseFormat()', function () {
        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->responseFormat(['type' => 'json_object'])
            ->withOptions(['text' => ['verbosity' => 'low', 'format' => ['type' => 'text']]])
            ->inputItems()->appendUserText('Hi');

        $builder->send();

        expect($this->responses->lastPayload['text'])->toBe([
            'format' => ['type' => 'json_object'],
            'verbosity' => 'low',
        ]);
    });

    it('ignores an idempotency key passed as an option', function () {
        config()->set('ai-assistant.responses.idempotency_enabled', false);
        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withOptions(['_idempotency_key' => 'mine'])
            ->inputItems()->appendUserText('Hi');
        $builder->send();

        expect($this->responses->lastPayload)->not->toHaveKey('_idempotency_key');

        config()->set('ai-assistant.responses.idempotency_enabled', true);
        $builder->send();

        expect($this->responses->lastPayload['_idempotency_key'])->toStartWith('resp_');
    });

    it('includes the options in the idempotency key', function () {
        config()->set('ai-assistant.responses.idempotency_enabled', true);
        // One bucket for the whole test, so the keys depend on the payload only
        config()->set('ai-assistant.responses.idempotency_bucket', 1_000_000_000);
        $keyFor = function (array $options): string {
            $builder = new ResponsesBuilder(app(AssistantService::class));
            $builder->inConversation('conv_1')->withOptions($options)->inputItems()->appendUserText('Hi');
            $builder->send();

            return $this->responses->lastPayload['_idempotency_key'];
        };

        expect($keyFor([]))->toBe($keyFor([]))
            ->and($keyFor(['multi_agent' => ['enabled' => true]]))->not->toBe($keyFor([]));
    });

    it('carries the options into the request that continues a turn after tool calls, without input or tool_choice', function () {
        $this->responses->pushResponse(ResponsesFactory::withToolCalls('conv_1', [
            ['id' => 'call_1', 'name' => 'unregistered_tool', 'arguments' => ['x' => 1]],
        ]));
        $this->responses->pushResponse(ResponsesFactory::afterToolResultsFinal('conv_1', 'done'));

        $builder = new ResponsesBuilder(app(AssistantService::class));
        $builder->inConversation('conv_1')
            ->withOptions([
                'multi_agent' => ['enabled' => true],
                'tool_choice' => 'required',
                'input' => [['role' => 'user', 'content' => 'From options']],
            ])
            ->inputItems()->appendUserText('Hi');

        $builder->send();

        expect($this->responses->createdPayloads)->toHaveCount(2);
        [$first, $continuation] = $this->responses->createdPayloads;
        expect($first)->toMatchArray(['multi_agent' => ['enabled' => true], 'tool_choice' => 'required'])
            ->and($first['input'][0]['content'][0]['text'])->toBe('Hi')
            ->and($continuation['multi_agent'])->toBe(['enabled' => true])
            ->and($continuation)->not->toHaveKey('input')
            ->and($continuation)->not->toHaveKey('tool_choice');
    });
});
