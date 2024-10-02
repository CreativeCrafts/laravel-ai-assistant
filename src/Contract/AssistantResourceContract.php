<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

use OpenAI\Client;
use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\ThreadResponse;

interface AssistantResourceContract
{
    public function __construct(?Client $client = null);
    public function createAssistant(array $parameters): AssistantResponse;
    public function getAssistantViaId(string $assistantId): AssistantResponse;
    public function createThread(array $parameters): ThreadResponse;
    public function writeMessage(string $threadId, array $messageData): ThreadMessageResponse;
    public function runMessageThread(string $threadId, array $messageData): bool;
}