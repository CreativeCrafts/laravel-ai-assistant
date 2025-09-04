<?php

declare(strict_types=1);

// Aliases to satisfy tests expecting the official OpenAI PHP SDK namespaces.
// These map our internal Compat classes to the external-facing FQCNs used in tests.

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
