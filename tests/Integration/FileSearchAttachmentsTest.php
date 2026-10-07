<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $fakeResponses = new FakeResponsesRepository();
    $fakeConversations = new FakeConversationsRepository();
    $fakeFiles = new FakeFilesRepository();

    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);
});

it('sends attachments as input_file blocks without an attachments field', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();
    $responses->pushResponse(ResponsesFactory::syncTextResponse($convId, 'ok'));

    $assistant->sendChatMessage($convId, 'with attachments', [
        'attachments' => [
            [ 'file_id' => 'file_777', 'tools' => [['type' => 'file_search']] ],
        ],
        'tools' => [],
    ]);

    $payload = $responses->lastPayload;
    expect($payload['input'][0]['content'])->toContain(['type' => 'input_file', 'file_id' => 'file_777'])
        ->and($payload['input'][0])->not->toHaveKey('attachments')
        ->and($payload)->not->toHaveKey('tools');
});

it('does not add a file_search tool for attached files', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();
    $responses->pushResponse(ResponsesFactory::syncTextResponse($convId, 'ok'));

    $assistant->sendChatMessage($convId, 'read attached', [
        'file_ids' => ['file_123'],
        'tools' => [],
        'use_file_search' => false,
    ]);

    $payload = $responses->lastPayload;
    $tools = $payload['tools'] ?? [];
    $hasFileSearch = false;
    foreach ($tools as $t) {
        if (($t['type'] ?? null) === 'file_search') {
        $hasFileSearch = true;
        break;
        }
    }
    expect($hasFileSearch)->toBeFalse();
});
