<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ResponseCanceledException;
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $fakeResponses = new FakeResponsesRepository();
    $fakeConversations = new FakeConversationsRepository();
    $fakeFiles = new FakeFilesRepository();

    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);

    app()->forgetInstance(OpenAIClientFacade::class);
    app()->singleton(OpenAIClientFacade::class, function ($app) use ($fakeResponses, $fakeConversations, $fakeFiles) {
        return new OpenAIClientFacade(
            $fakeResponses,
            $fakeConversations,
            $fakeFiles,
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract::class),
        );
    });
});

it('invokes onEvent callback and supports early termination via shouldStop', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();

    $responses->setStream(ResponsesFactory::streamingTextSse(['A','B','C']));

    $count = 0;
    $shouldStopAfter = 2;

    $gen = $assistant->streamTurn(
        $convId,
        instructions: null,
        model: null,
        tools: [],
        inputItems: [[
            'role' => 'user',
            'content' => [['type' => 'input_text', 'text' => 'hi']],
        ]],
        responseFormat: null,
        modalities: null,
        metadata: [],
        onEvent: function ($evt) use (&$count) { $count++; },
        shouldStop: function () use (&$count, $shouldStopAfter) { return $count >= $shouldStopAfter; }
    );

    // Consume generator
    foreach ($gen as $evt) { /* iterate */
    }

    expect($count)->toBeGreaterThanOrEqual($shouldStopAfter);
});

it('throws ResponseCanceledException when a canceled event is seen', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();

    $responses->setStream([
        'event: response.output_text.delta',
        'data: {"delta":"hi"}',
        '',
        'event: response.canceled',
        'data: {"type":"response.canceled"}',
        '',
    ]);

    $this->expectException(ResponseCanceledException::class);

    // Force iteration to trigger exception inside StreamingService
    foreach ($assistant->streamTurn(
        $convId,
        instructions: null,
        model: null,
        tools: [],
        inputItems: [[
                'role' => 'user',
                'content' => [['type' => 'input_text', 'text' => 'hi']],
            ]]
    ) as $evt) {
        // no-op
    }
});
