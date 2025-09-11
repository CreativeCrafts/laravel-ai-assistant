<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\OpenAiTransportException;
use CreativeCrafts\LaravelAiAssistant\Support\Retry;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Repository implementation for OpenAI API operations.
 * This class wraps the OpenAI client and provides a clean abstraction
 * layer for all API operations used throughout the application.
 */
class OpenAiRepository implements OpenAiRepositoryContract
{
    public function __construct(
        protected Client $client
    ) {
    }

    /**
     * Create a new assistant.
     *
     * @param array $parameters Parameters for creating the assistant
     * @return AssistantResponse
     */
    public function createAssistant(array $parameters): AssistantResponse
    {
        return $this->execute(fn () => $this->client->assistants()->create($parameters));
    }

    /**
     * Retrieve an assistant by ID.
     *
     * @param string $assistantId The assistant ID
     * @return AssistantResponse
     */
    public function retrieveAssistant(string $assistantId): AssistantResponse
    {
        return $this->execute(fn () => $this->client->assistants()->retrieve($assistantId));
    }

    /**
     * Create a new thread.
     *
     * @param array $parameters Parameters for creating the thread
     * @return ThreadResponse
     */
    public function createThread(array $parameters): ThreadResponse
    {
        return $this->execute(fn () => $this->client->threads()->create($parameters));
    }

    /**
     * Create a message in a thread.
     *
     * @param string $threadId The thread ID
     * @param array $messageData The message data
     * @return ThreadMessageResponse
     */
    public function createThreadMessage(string $threadId, array $messageData): ThreadMessageResponse
    {
        return $this->execute(fn () => $this->client->threads()->messages()->create(
            $threadId,
            $messageData
        ));
    }

    /**
     * Create a run for a thread.
     *
     * @param string $threadId The thread ID
     * @param array $parameters Run parameters
     * @return ThreadRunResponse
     */
    public function createThreadRun(string $threadId, array $parameters): ThreadRunResponse
    {
        return $this->execute(fn () => $this->client->threads()->runs()->create(
            $threadId,
            $parameters
        ));
    }

    /**
     * Retrieve a thread run.
     *
     * @param string $threadId The thread ID
     * @param string $runId The run ID
     * @return ThreadRunResponse
     */
    public function retrieveThreadRun(string $threadId, string $runId): ThreadRunResponse
    {
        return $this->execute(fn () => $this->client->threads()->runs()->retrieve(
            $threadId,
            $runId
        ));
    }

    /**
     * List messages in a thread.
     *
     * @param string $threadId The thread ID
     * @return array
     */
    public function listThreadMessages(string $threadId): array
    {
        return $this->execute(fn () => $this->client->threads()->messages()->list($threadId)->toArray());
    }

    /**
     * Create a text completion.
     *
     * @param array $parameters Completion parameters
     * @return CompletionResponse
     */
    public function createCompletion(array $parameters): CompletionResponse
    {
        return $this->execute(fn () => $this->client->completions()->create($parameters));
    }

    /**
     * Create a streamed text completion.
     *
     * @param array $parameters Completion parameters
     * @return iterable
     */
    public function createStreamedCompletion(array $parameters): iterable
    {
        return $this->execute(fn () => $this->client->completions()->createStreamed($parameters));
    }

    /**
     * Create a chat completion.
     *
     * @param array $parameters Chat completion parameters
     * @return ChatResponse
     */
    public function createChatCompletion(array $parameters): ChatResponse
    {
        return $this->execute(fn () => $this->client->chat()->create($parameters));
    }

    /**
     * Create a streamed chat completion.
     *
     * @param array $parameters Chat completion parameters
     * @return iterable
     */
    public function createStreamedChatCompletion(array $parameters): iterable
    {
        return $this->execute(fn () => $this->client->chat()->createStreamed($parameters));
    }

    /**
     * Transcribe audio to text.
     *
     * @param array $parameters Transcription parameters
     * @return TranscriptionResponse
     */
    public function transcribeAudio(array $parameters): TranscriptionResponse
    {
        return $this->execute(fn () => $this->client->audio()->transcribe($parameters));
    }

    /**
     * Translate audio to text.
     *
     * @param array $parameters Translation parameters
     * @return TranslationResponse
     */
    public function translateAudio(array $parameters): TranslationResponse
    {
        return $this->execute(fn () => $this->client->audio()->translate($parameters));
    }

    /**
     * Execute a repository call and wrap transport errors.
     * @template T
     *
     * @param callable():T $callback
     * @return T
     * @throws OpenAiTransportException
     */
    private function execute(callable $callback)
    {
        $max = Config::integer(key: 'ai-assistant.transport.max_retries', default: 2);
        $initial = Config::integer(key: 'ai-assistant.transport.initial_delay_ms', default: 200);
        $maxDelay = Config::integer(key: 'ai-assistant.transport.max_delay_ms', default: 2000);
        $delays = Retry::backoffDelays($max, $initial, $maxDelay);

        $attempt = 0;
        while (true) {
            try {
                return $callback();
            } catch (Throwable $e) {
                if ($attempt >= count($delays) || !Retry::shouldRetry($e)) {
                    throw OpenAiTransportException::from($e);
                }
                Retry::usleepMs($delays[$attempt]);
                $attempt++;
                continue;
            }
        }
    }
}
