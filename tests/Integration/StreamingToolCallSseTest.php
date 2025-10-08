<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;
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
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract::class),
        );
    });
});

it('streams tool_call created events and completes', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();

    $responses->setStream(ResponsesFactory::streamingToolCallSse('lookup', ['q' => 'hello']));

    $events = iterator_to_array($assistant->getStreamingResponse($convId, 'hi'));

    $types = array_map(fn ($e) => $e['type'] ?? '', $events);
    expect($types)->toContain('response.tool_call.created')->toContain('response.completed');
});
