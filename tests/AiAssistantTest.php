<?php

use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;


it('can have a chat conversation', function(): void {
    $chatResponse = AiAssistant::acceptPrompt('What is world health organisation?')->andRespond();
    expect($chatResponse)->toBeArray()
        ->toHaveKey('role', 'assistant')
        ->toHaveKey('content');
})->group('chat');
