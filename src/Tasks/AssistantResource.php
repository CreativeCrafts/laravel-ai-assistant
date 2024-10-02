<?php

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Contract\AssistantResourceContract;
use OpenAI\Client;
use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\ThreadResponse;

class AssistantResource implements AssistantResourceContract
{
    protected Client $client;
    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? AppConfig::openAiClient();
    }

    public function createAssistant(array $parameters): AssistantResponse
    {
        return $this->client->assistants()->create($parameters);
    }

    public function getAssistantViaId(string $assistantId): AssistantResponse
    {
        return $this->client->assistants()->retrieve($assistantId);
    }

    public function createThread(array $parameters): ThreadResponse
    {
        return $this->client->threads()->create($parameters);
    }

    public function writeMessage(string $threadId, array $messageData): ThreadMessageResponse
    {
        return $this->client->threads()->messages()->create(
            $threadId,
            $messageData
        );
    }

    public function runMessageThread(string $threadId, array $messageData): bool
    {
        $run = $this->client->threads()->runs()->create(
            $threadId,
            $messageData
        );

        do {
            sleep(1);
            $run = $this->client->threads()->runs()->retrieve(
                threadId: $run->threadId,
                runId: $run->id
            );
        } while ($run->status !== 'completed');

        return true;
    }

    public function listMessages(string $threadId): string
    {
        $messages = $this->client->threads()->messages()->list($threadId)->toArray();
        return $messages['data'][0]['content'][0]['text']['value'] ?? '';
    }
}