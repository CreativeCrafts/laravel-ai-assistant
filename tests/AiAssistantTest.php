<?php

use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;

it('throws InvalidApiKeyException when an invalid open ai key or organisation is provided', function () {
    $blogIdea = AiAssistant::acceptPrompt('How to make money online?')->brainstorm();
})->throws(InvalidApiKeyException::class, 'Invalid OpenAI API key or organization.');
