<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantResourceContract;
use OpenAI\Client;
use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\ThreadResponse;
use Symfony\Component\HttpFoundation\Response;

class AssistantService implements AssistantResourceContract
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
            // @pest-mutate-ignore
        } while ($run->status !== 'completed');

        return true;
    }

    public function listMessages(string $threadId): string
    {
        $messages = $this->client->threads()->messages()->list($threadId)->toArray();
        // @pest-mutate-ignore
        return $messages['data'][0]['content'][0]['text']['value'] ?? '';
    }

    public function transcribeTo(array $payload): string
    {
        return $this->client->audio()->transcribe($payload)->text;
    }

    public function translateTo(array $payload): string
    {
        return $this->client->audio()->translate($payload)->text;
    }

    public function textCompletion(array $payload): string
    {
        $choices = $this->client->completions()->create($payload)->choices;

        if ($choices === []) {
            return '';
        }

        return trim($choices[count($choices) - 1]->text);
    }

    public function streamedCompletion(array $payload): string
    {
        $streamResponses = $this->client->completions()->createStreamed($payload);
        foreach ($streamResponses as $response) {
            /** @var Response $response */
            if (isset($response->choices[0]->text)) {
                return $response->choices[0]->text;
            }
        }

        return '';
    }

    public function chatTextCompletion(array $payload): array
    {
        $choices = $this->client->chat()->create($payload)->choices;
        if ($choices === []) {
            return [];
        }
        return $choices[0]->message->toArray();
    }

    public function streamedChat(array $payload): array
    {
        $streamResponses = $this->client->chat()->createStreamed($payload);
        foreach ($streamResponses as $response) {
            /** @var Response $response */
            if (isset($response->choices[0])) {
                return $response->choices[0]->toArray();
            }
        }

        return [];
    }
}
