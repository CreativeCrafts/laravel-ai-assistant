<?php

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;

it('throws InvalidApiKeyException when an invalid open ai key or organisation is provided', function () {
    $blogIdea = AiAssistant::acceptPrompt('How to make money online?')->draft();
})->throws(InvalidApiKeyException::class, 'Invalid OpenAI API key or organization.');
