<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;

it('indexes and queries response status by conversation id', function () {
    $store = app(ResponseStatusStore::class);
    $respId = 'resp_test_1';
    $convId = 'conv_test_1';

    $payload = [
        'response' => [
            'id' => $respId,
            'conversation_id' => $convId,
            'status' => 'completed',
        ],
    ];

    $store->setStatus($respId, 'completed', $payload, 60);

    $byConv = $store->getByConversationId($convId);
    expect($byConv)->toBeArray()
        ->and($byConv['status'] ?? null)->toBe('completed')
        ->and($byConv['last_response_id'] ?? null)->toBe($respId);

    expect($store->getLastStatusByConversation($convId))->toBe('completed');
});

it('returns last status by response id', function () {
    $store = app(ResponseStatusStore::class);
    $respId = 'resp_test_2';

    $store->setStatus($respId, 'failed', [], 60);

    expect($store->getLastStatus($respId))->toBe('failed');
});
