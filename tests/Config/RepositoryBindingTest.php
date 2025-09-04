<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\NullOpenAiRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ConfigurationValidationException;

it('binds OpenAiRepository by default', function () {
    config(['ai-assistant.mock_responses' => false]);
    config(['ai-assistant.repository' => null]);

    $instance = app()->make(OpenAiRepositoryContract::class);

    expect($instance)->toBeInstanceOf(OpenAiRepository::class);
});

it('binds NullOpenAiRepository when mock_responses is true', function () {
    config(['ai-assistant.mock_responses' => true]);
    config(['ai-assistant.repository' => null]);

    $instance = app()->make(OpenAiRepositoryContract::class);

    expect($instance)->toBeInstanceOf(NullOpenAiRepository::class);
});

it('binds custom repository when repository override is provided and valid', function () {
    config(['ai-assistant.mock_responses' => false]);
    config(['ai-assistant.repository' => OpenAiRepository::class]);

    $instance = app()->make(OpenAiRepositoryContract::class);

    expect($instance)->toBeInstanceOf(OpenAiRepository::class);
});

it('throws when repository override does not implement the contract', function () {
    config(['ai-assistant.mock_responses' => false]);
    // stdClass exists but does not implement the contract
    config(['ai-assistant.repository' => stdClass::class]);

    app()->make(OpenAiRepositoryContract::class);
})->throws(ConfigurationValidationException::class);
