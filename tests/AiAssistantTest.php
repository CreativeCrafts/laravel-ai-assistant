<?php

use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;

it('can have a chat conversation', function(): void {
    $chatResponse = AiAssistant::acceptQuestion('What is world health organisation?')->andRespondWith();
    expect($chatResponse)->toBeArray()
        ->toHaveKey('role', 'assistant')
        ->toHaveKey('content');
})->group('chat');
