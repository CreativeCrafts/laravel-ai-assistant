<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface ChatTextCompletionContract
{
    public function __invoke(array $payload): array;

    public function chatTextCompletion(array $payload): array;

    public function streamedChat(array $payload): array;

    public static function messages(string $prompt): array;

    public static function cacheChatConversation(array $conversation): void;
}
