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

it('supports Responses list/get/cancel/delete and respects payload tools', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();

    // Queue two responses and verify list
    $responses->pushResponse(ResponsesFactory::syncTextResponse($convId, 'one'));
    $responses->pushResponse(ResponsesFactory::syncTextResponse($convId, 'two'));
    $list = $responses->listResponses();
    expect(count($list['data'] ?? []))->toBeGreaterThanOrEqual(2);

    // Create response with file_ids should auto-enable file_search
    $assistant->sendChatMessage($convId, 'with files', [
        'file_ids' => ['file_1'],
        'tools' => [],
    ]);
    $payload = $responses->lastPayload;
    $hasFileSearch = collect($payload['tools'] ?? [])->contains(fn ($t) => ($t['type'] ?? null) === 'file_search');
    expect($hasFileSearch)->toBeTrue();

    // Last queued response becomes lastResponse
    $created = $responses->createResponse(['conversation' => $convId]);
    expect($created['conversation_id'] ?? null)->toBe($convId);

    // get/cancel/delete paths
    $rid = $created['id'] ?? 'resp_x';
    $got = $responses->getResponse($rid);
    expect($got['id'] ?? null)->toBe($rid);

    expect($responses->cancelResponse($rid))->toBeTrue();
    expect($responses->deleteResponse($rid))->toBeTrue();
});
