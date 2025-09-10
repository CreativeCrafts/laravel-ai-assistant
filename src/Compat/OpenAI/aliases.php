<?php

declare(strict_types=1);

$__ai_aliases_enabled = getenv('AI_ASSISTANT_COMPAT_OPENAI_ALIASES');
if ($__ai_aliases_enabled === false) {
    $__ai_aliases_enabled = '1';
}
if (in_array(strtolower((string)$__ai_aliases_enabled), ['0', 'false', 'off', 'no'], true)) {
    return;
}

// Aliases to satisfy tests expecting the official OpenAI PHP SDK namespaces.
// These map our internal Compat classes to the external-facing FQCNs used in tests.

// Client (only if SDK not installed)
if (!class_exists('OpenAI\\Client')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client::class,
        'OpenAI\\Client'
    );
}

// Assistants
if (!class_exists('OpenAI\\Responses\\Assistants\\AssistantResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse::class,
        'OpenAI\\Responses\\Assistants\\AssistantResponse'
    );
}

// Threads
if (!class_exists('OpenAI\\Responses\\Threads\\ThreadResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse::class,
        'OpenAI\\Responses\\Threads\\ThreadResponse'
    );
}

// Thread Messages
if (!class_exists('OpenAI\\Responses\\Threads\\Messages\\ThreadMessageResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse::class,
        'OpenAI\\Responses\\Threads\\Messages\\ThreadMessageResponse'
    );
}

// Thread Messages List
if (!class_exists('OpenAI\\Responses\\Threads\\Messages\\ThreadMessageListResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageListResponse::class,
        'OpenAI\\Responses\\Threads\\Messages\\ThreadMessageListResponse'
    );
}

// Thread Runs
if (!class_exists('OpenAI\\Responses\\Threads\\Runs\\ThreadRunResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse::class,
        'OpenAI\\Responses\\Threads\\Runs\\ThreadRunResponse'
    );
}

// Chat
if (!class_exists('OpenAI\\Responses\\Chat\\CreateResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse::class,
        'OpenAI\\Responses\\Chat\\CreateResponse'
    );
}

// Completions
if (!class_exists('OpenAI\\Responses\\Completions\\CreateResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse::class,
        'OpenAI\\Responses\\Completions\\CreateResponse'
    );
}
if (!class_exists('OpenAI\\Responses\\Completions\\StreamedCompletionResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\StreamedCompletionResponse::class,
        'OpenAI\\Responses\\Completions\\StreamedCompletionResponse'
    );
}

// Audio
if (!class_exists('OpenAI\\Responses\\Audio\\TranscriptionResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse::class,
        'OpenAI\\Responses\\Audio\\TranscriptionResponse'
    );
}
if (!class_exists('OpenAI\\Responses\\Audio\\TranslationResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse::class,
        'OpenAI\\Responses\\Audio\\TranslationResponse'
    );
}

// Meta
if (!class_exists('OpenAI\\Responses\\Meta\\MetaInformation')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation::class,
        'OpenAI\\Responses\\Meta\\MetaInformation'
    );
}

// StreamResponse
if (!class_exists('OpenAI\\Responses\\StreamResponse')) {
    class_alias(
        CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\StreamResponse::class,
        'OpenAI\\Responses\\StreamResponse'
    );
}
